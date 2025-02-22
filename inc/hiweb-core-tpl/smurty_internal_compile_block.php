<?php

/**
 * hiweb_tpl Internal Plugin Compile Block
 * Compiles the {block}{/block} tags
 *
 * @package    hiweb_tpl
 * @subpackage Compiler
 * @author     Uwe Tews
 */

/**
 * hiweb_tpl Internal Plugin Compile Block Class
 *
 * @package    hiweb_tpl
 * @subpackage Compiler
 */
class Smurty_Internal_Compile_Block extends Smurty_Internal_CompileBase
{

    const parent = '____SMURTY_BLOCK_PARENT____';
    /**
     * Attribute definition: Overwrites base class.
     *
     * @var array
     * @see Smurty_Internal_CompileBase
     */
    public $required_attributes = array('name');

    /**
     * Attribute definition: Overwrites base class.
     *
     * @var array
     * @see Smurty_Internal_CompileBase
     */
    public $shorttag_order = array('name');

    /**
     * Attribute definition: Overwrites base class.
     *
     * @var array
     * @see Smurty_Internal_CompileBase
     */
    public $option_flags = array('hide', 'append', 'prepend', 'nocache');

    /**
     * Attribute definition: Overwrites base class.
     *
     * @var array
     * @see Smurty_Internal_CompileBase
     */
    public $optional_attributes = array('internal_file', 'internal_uid', 'internal_line');
    /**
     * nested child block names
     *
     * @var array
     */
    public static $nested_block_names = array();

    /**
     * child block source buffer
     *
     * @var array
     */
    public static $block_data = array();

    /**
     * Compiles code for the {block} tag
     *
     * @param array  $args     array with attributes from parser
     * @param object $compiler compiler object
     *
     * @return boolean true
     */
    public function compile($args, $compiler)
    {
        // check and get attributes
        $_attr = $this->getAttributes($compiler, $args);
        $_name = trim($_attr['name'], "\"'");

        // existing child must override parent settings
        if (isset($compiler->template->block_data[$_name]) && $compiler->template->block_data[$_name]['mode'] == 'replace') {
            $_attr['append'] = false;
            $_attr['prepend'] = false;
        }

        // check if we process an inheritance child template
        if ($compiler->inheritance_child) {
            array_unshift(self::$nested_block_names, $_name);
            // build {block} for child block
            self::$block_data[$_name]['source'] =
                "{$compiler->smurty->left_delimiter}private_child_block name={$_attr['name']} file='{$compiler->template->source->filepath}' type='{$compiler->template->source->type}' resource='{$compiler->template->template_resource}'" .
                " uid='{$compiler->template->source->uid}' line={$compiler->lex->line}";
            if ($_attr['nocache']) {
                self::$block_data[$_name]['source'] .= ' nocache';
            }
            self::$block_data[$_name]['source'] .= $compiler->smurty->right_delimiter;

            $save = array($_attr, $compiler->inheritance);
            $this->openTag($compiler, 'block', $save);
            // set flag for {block} tag
            $compiler->inheritance = true;
            $compiler->lex->yypushstate(Smurty_Internal_Templatelexer::CHILDBLOCK);
            $compiler->has_code = false;
            return;
        }
        // must merge includes
        if ($_attr['nocache'] == true) {
            $compiler->tag_nocache = true;
        }
        $save = array($_attr, $compiler->inheritance, $compiler->parser->current_buffer, $compiler->nocache);
        $this->openTag($compiler, 'block', $save);
        $compiler->inheritance = true;
        $compiler->nocache = $compiler->nocache | $compiler->tag_nocache;

        $compiler->parser->current_buffer = new _smurty_template_buffer($compiler->parser);
        $compiler->has_code = false;

        return true;
    }

    /**
     * Compile saved child block source
     *
     * @param object $compiler compiler object
     * @param string $_name    optional name of child block
     *
     * @return string   compiled code of child block
     */
    static function compileChildBlock($compiler, $_name = null)
    {
        if ($compiler->inheritance_child) {
            $name1 = Smurty_Internal_Compile_Block::$nested_block_names[0];
            if (isset($compiler->template->block_data[$name1])) {
                //  replace inner block name with generic
                Smurty_Internal_Compile_Block::$block_data[$name1]['source'] .= $compiler->template->block_data[$name1]['source'];
                Smurty_Internal_Compile_Block::$block_data[$name1]['child'] = true;
            }
            $compiler->lex->yypushstate(Smurty_Internal_Templatelexer::CHILDBLOCK);
            $compiler->has_code = false;
            return;
        }
        // if called by {$smurty.block.child} we must search the name of enclosing {block}
        if ($_name == null) {
            $stack_count = count($compiler->_tag_stack);
            while (--$stack_count >= 0) {
                if ($compiler->_tag_stack[$stack_count][0] == 'block') {
                    $_name = trim($compiler->_tag_stack[$stack_count][1][0]['name'], "\"'");
                    break;
                }
            }
        }
        if ($_name == null) {
            $compiler->trigger_template_error(' tag {$smurty.block.child} used outside {block} tags ', $compiler->lex->taglineno);
        }
        // undefined child?
        if (!isset($compiler->template->block_data[$_name]['source'])) {
            $compiler->popTrace();
            return '';
        }
        // flag that child is already compile by {$smurty.block.child} inclusion
        $compiler->template->block_data[$_name]['compiled'] = true;
        $_tpl = new Smurty_Internal_template('string:' . $compiler->template->block_data[$_name]['source'], $compiler->smurty, $compiler->template, $compiler->template->cache_id,
                                             $compiler->template->compile_id, $compiler->template->caching, $compiler->template->cache_lifetime);
        if ($compiler->smurty->debugging) {
            Smurty_Internal_Debug::ignore($_tpl);
        }
        $_tpl->tpl_vars = $compiler->template->tpl_vars;
        $_tpl->variable_filters = $compiler->template->variable_filters;
        $_tpl->properties['nocache_hash'] = $compiler->template->properties['nocache_hash'];
        $_tpl->allow_relative_path = true;
        $_tpl->compiler->inheritance = true;
        $_tpl->compiler->suppressHeader = true;
        $_tpl->compiler->suppressFilter = true;
        $_tpl->compiler->suppressTemplatePropertyHeader = true;
        $_tpl->compiler->suppressMergedTemplates = true;
        $nocache = $compiler->nocache || $compiler->tag_nocache;
        if (strpos($compiler->template->block_data[$_name]['source'], self::parent) !== false) {
            $_output = str_replace(self::parent, $compiler->parser->current_buffer->to_smurty_php(), $_tpl->compiler->compileTemplate($_tpl, $nocache));
        } elseif ($compiler->template->block_data[$_name]['mode'] == 'prepend') {
            $_output = $_tpl->compiler->compileTemplate($_tpl, $nocache) . $compiler->parser->current_buffer->to_smurty_php();
        } elseif ($compiler->template->block_data[$_name]['mode'] == 'append') {
            $_output = $compiler->parser->current_buffer->to_smurty_php() . $_tpl->compiler->compileTemplate($_tpl, $nocache);
        } elseif (!empty($compiler->template->block_data[$_name])) {
            $_output = $_tpl->compiler->compileTemplate($_tpl, $nocache);
        }
        $compiler->template->properties['file_dependency'] = array_merge($compiler->template->properties['file_dependency'], $_tpl->properties['file_dependency']);
        $compiler->template->properties['function'] = array_merge($compiler->template->properties['function'], $_tpl->properties['function']);
        $compiler->merged_templates = array_merge($compiler->merged_templates, $_tpl->compiler->merged_templates);
        $compiler->template->variable_filters = $_tpl->variable_filters;
        if ($_tpl->has_nocache_code) {
            $compiler->template->has_nocache_code = true;
        }
        foreach ($_tpl->required_plugins as $key => $tmp1) {
            if ($compiler->nocache && $compiler->template->caching) {
                $code = 'nocache';
            } else {
                $code = $key;
            }
            foreach ($tmp1 as $name => $tmp) {
                foreach ($tmp as $type => $data) {
                    $compiler->template->required_plugins[$code][$name][$type] = $data;
                }
            }
        }
        unset($_tpl);
        $compiler->has_code = true;
        return $_output;
    }

    /**
     * Compile $smurty.block.parent
     *
     * @param object $compiler compiler object
     * @param string $_name    optional name of child block
     *
     * @return string   compiled code of child block
     */
    static function compileParentBlock($compiler, $_name = null)
    {
        // if called by {$smurty.block.parent} we must search the name of enclosing {block}
        if ($_name == null) {
            $stack_count = count($compiler->_tag_stack);
            while (--$stack_count >= 0) {
                if ($compiler->_tag_stack[$stack_count][0] == 'block') {
                    $_name = trim($compiler->_tag_stack[$stack_count][1][0]['name'], "\"'");
                    break;
                }
            }
        }
        if ($_name == null) {
            $compiler->trigger_template_error(' tag {$smurty.block.parent} used outside {block} tags ', $compiler->lex->taglineno);
        }
        if (empty(Smurty_Internal_Compile_Block::$nested_block_names)) {
            $compiler->trigger_template_error(' illegal {$smurty.block.parent} in parent template ', $compiler->lex->taglineno);
        }
        Smurty_Internal_Compile_Block::$block_data[Smurty_Internal_Compile_Block::$nested_block_names[0]]['source'] .= Smurty_Internal_Compile_Block::parent;
        $compiler->lex->yypushstate(Smurty_Internal_Templatelexer::CHILDBLOCK);
        $compiler->has_code = false;
        return;
    }

    /**
     * Process block source
     *
     * @param        $compiler
     * @param string $source source text
     *
     */
    static function blockSource($compiler, $source)
    {
        Smurty_Internal_Compile_Block::$block_data[Smurty_Internal_Compile_Block::$nested_block_names[0]]['source'] .= $source;
    }
}

/**
 * hiweb_tpl Internal Plugin Compile BlockClose Class
 *
 * @package    hiweb_tpl
 * @subpackage Compiler
 */
class Smurty_Internal_Compile_Blockclose extends Smurty_Internal_CompileBase
{
    /**
     * Compiles code for the {/block} tag
     *
     * @param array  $args     array with attributes from parser
     * @param object $compiler compiler object
     *
     * @return string compiled code
     */
    public function compile($args, $compiler)
    {
        $compiler->has_code = true;
        // check and get attributes
        $_attr = $this->getAttributes($compiler, $args);
        $saved_data = $this->closeTag($compiler, array('block'));
        $_name = trim($saved_data[0]['name'], "\"'");
        // reset flag for {block} tag
        $compiler->inheritance = $saved_data[1];
        // check if we process an inheritance child template
        if ($compiler->inheritance_child) {
            $name1 = Smurty_Internal_Compile_Block::$nested_block_names[0];
            Smurty_Internal_Compile_Block::$block_data[$name1]['source'] .= "{$compiler->smurty->left_delimiter}/private_child_block{$compiler->smurty->right_delimiter}";
            array_shift(Smurty_Internal_Compile_Block::$nested_block_names);
            if (!empty(Smurty_Internal_Compile_Block::$nested_block_names)) {
                $name2 = Smurty_Internal_Compile_Block::$nested_block_names[0];
                if (isset($compiler->template->block_data[$name1]) || !$saved_data[0]['hide']) {
                    if (isset(Smurty_Internal_Compile_Block::$block_data[$name1]['child']) || !isset($compiler->template->block_data[$name1])) {
                        Smurty_Internal_Compile_Block::$block_data[$name2]['source'] .= Smurty_Internal_Compile_Block::$block_data[$name1]['source'];
                    } else {
                        if ($compiler->template->block_data[$name1]['mode'] == 'append') {
                            Smurty_Internal_Compile_Block::$block_data[$name2]['source'] .= Smurty_Internal_Compile_Block::$block_data[$name1]['source'] . $compiler->template->block_data[$name1]['source'];
                        } elseif ($compiler->template->block_data[$name1]['mode'] == 'prepend') {
                            Smurty_Internal_Compile_Block::$block_data[$name2]['source'] .= $compiler->template->block_data[$name1]['source'] . Smurty_Internal_Compile_Block::$block_data[$name1]['source'];
                        } else {
                            Smurty_Internal_Compile_Block::$block_data[$name2]['source'] .= $compiler->template->block_data[$name1]['source'];
                        }
                    }
                }
                unset(Smurty_Internal_Compile_Block::$block_data[$name1]);
                $compiler->lex->yypushstate(Smurty_Internal_Templatelexer::CHILDBLOCK);
            } else {
                if (isset($compiler->template->block_data[$name1]) || !$saved_data[0]['hide']) {
                    if (isset($compiler->template->block_data[$name1]) && !isset(Smurty_Internal_Compile_Block::$block_data[$name1]['child'])) {
                        if (strpos($compiler->template->block_data[$name1]['source'], Smurty_Internal_Compile_Block::parent) !== false) {
                            $compiler->template->block_data[$name1]['source'] =
                                str_replace(Smurty_Internal_Compile_Block::parent, Smurty_Internal_Compile_Block::$block_data[$name1]['source'], $compiler->template->block_data[$name1]['source']);
                        } elseif ($compiler->template->block_data[$name1]['mode'] == 'prepend') {
                            $compiler->template->block_data[$name1]['source'] .= Smurty_Internal_Compile_Block::$block_data[$name1]['source'];
                        } elseif ($compiler->template->block_data[$name1]['mode'] == 'append') {
                            $compiler->template->block_data[$name1]['source'] = Smurty_Internal_Compile_Block::$block_data[$name1]['source'] . $compiler->template->block_data[$name1]['source'];
                        }
                    } else {
                        $compiler->template->block_data[$name1]['source'] = Smurty_Internal_Compile_Block::$block_data[$name1]['source'];
                    }
                    $compiler->template->block_data[$name1]['mode'] = 'replace';
                    if ($saved_data[0]['append']) {
                        $compiler->template->block_data[$name1]['mode'] = 'append';
                    }
                    if ($saved_data[0]['prepend']) {
                        $compiler->template->block_data[$name1]['mode'] = 'prepend';
                    }
                }
                unset(Smurty_Internal_Compile_Block::$block_data[$name1]);
                $compiler->lex->yypushstate(Smurty_Internal_Templatelexer::CHILDBODY);
            }
            $compiler->has_code = false;
            return;
        }
        if (isset($compiler->template->block_data[$_name]) && !isset($compiler->template->block_data[$_name]['compiled'])) {
            $_output = Smurty_Internal_Compile_Block::compileChildBlock($compiler, $_name);
        } else {
            if ($saved_data[0]['hide'] && !isset($compiler->template->block_data[$_name]['source'])) {
                $_output = '';
            } else {
                $_output = $compiler->parser->current_buffer->to_smurty_php();
            }
        }
        unset($compiler->template->block_data[$_name]['compiled']);
        // reset flags
        $compiler->parser->current_buffer = $saved_data[2];
        if ($compiler->nocache) {
            $compiler->tag_nocache = true;
        }
        $compiler->nocache = $saved_data[3];
        // $_output content has already nocache code processed
        $compiler->suppressNocacheProcessing = true;

        return $_output;
    }
}

/**
 * hiweb_tpl Internal Plugin Compile Child Block Class
 *
 * @package    hiweb_tpl
 * @subpackage Compiler
 */
class Smurty_Internal_Compile_Private_Child_Block extends Smurty_Internal_CompileBase
{

    /**
     * Attribute definition: Overwrites base class.
     *
     * @var array
     * @see Smurty_Internal_CompileBase
     */
    public $required_attributes = array('name', 'file', 'uid', 'line', 'type', 'resource');

    /**
     * Compiles code for the {private_child_block} tag
     *
     * @param array  $args     array with attributes from parser
     * @param object $compiler compiler object
     *
     * @return boolean true
     */
    public function compile($args, $compiler)
    {
        // check and get attributes
        $_attr = $this->getAttributes($compiler, $args);

        // update template with original template resource of {block}
        if (trim($_attr['type'], "'") == 'file') {
            $compiler->template->template_resource = 'file:' . realpath(trim($_attr['file'], "'"));
        } else {
            $compiler->template->template_resource = trim($_attr['resource'], "'");
        }
        // source object
        unset ($compiler->template->source);
        $exists = $compiler->template->source->exists;

        // must merge includes
        if ($_attr['nocache'] == true) {
            $compiler->tag_nocache = true;
        }
        $save = array($_attr, $compiler->nocache);

        // set trace back to child block
        $compiler->pushTrace(trim($_attr['file'], "\"'"), trim($_attr['uid'], "\"'"), $_attr['line'] - $compiler->lex->line);

        $this->openTag($compiler, 'private_child_block', $save);

        $compiler->nocache = $compiler->nocache | $compiler->tag_nocache;
        $compiler->has_code = false;

        return true;
    }
}

/**
 * hiweb_tpl Internal Plugin Compile Child Block Close Class
 *
 * @package    hiweb_tpl
 * @subpackage Compiler
 */
class Smurty_Internal_Compile_Private_Child_Blockclose extends Smurty_Internal_CompileBase
{

    /**
     * Compiles code for the {/private_child_block} tag
     *
     * @param array  $args     array with attributes from parser
     * @param object $compiler compiler object
     *
     * @return boolean true
     */
    public function compile($args, $compiler)
    {
        // check and get attributes
        $_attr = $this->getAttributes($compiler, $args);

        $saved_data = $this->closeTag($compiler, array('private_child_block'));

        // end of child block
        $compiler->popTrace();

        $compiler->nocache = $saved_data[1];
        $compiler->has_code = false;

        return true;
    }
}
