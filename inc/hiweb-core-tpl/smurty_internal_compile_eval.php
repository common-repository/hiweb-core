<?php
/**
 * hiweb_tpl Internal Plugin Compile Eval
 * Compiles the {eval} tag.
 *
 * @package    hiweb_tpl
 * @subpackage Compiler
 * @author     Uwe Tews
 */

/**
 * hiweb_tpl Internal Plugin Compile Eval Class
 *
 * @package    hiweb_tpl
 * @subpackage Compiler
 */
class Smurty_Internal_Compile_Eval extends Smurty_Internal_CompileBase
{
    /**
     * Attribute definition: Overwrites base class.
     *
     * @var array
     * @see Smurty_Internal_CompileBase
     */
    public $required_attributes = array('var');
    /**
     * Attribute definition: Overwrites base class.
     *
     * @var array
     * @see Smurty_Internal_CompileBase
     */
    public $optional_attributes = array('assign');
    /**
     * Attribute definition: Overwrites base class.
     *
     * @var array
     * @see Smurty_Internal_CompileBase
     */
    public $shorttag_order = array('var', 'assign');

    /**
     * Compiles code for the {eval} tag
     *
     * @param  array  $args     array with attributes from parser
     * @param  object $compiler compiler object
     *
     * @return string compiled code
     */
    public function compile($args, $compiler)
    {
        $this->required_attributes = array('var');
        $this->optional_attributes = array('assign');
        // check and get attributes
        $_attr = $this->getAttributes($compiler, $args);
        if (isset($_attr['assign'])) {
            // output will be stored in a smurty variable instead of being displayed
            $_assign = $_attr['assign'];
        }

        // create template object
        $_output = "\$_template = new {$compiler->smurty->template_class}('eval:'." . $_attr['var'] . ", \$_smurty_tpl->smurty, \$_smurty_tpl);";
        //was there an assign attribute?
        if (isset($_assign)) {
            $_output .= "\$_smurty_tpl->assign($_assign,\$_template->fetch());";
        } else {
            $_output .= "echo \$_template->fetch();";
        }

        return "<?php $_output ?>";
    }
}
