<?php
/**
 * Project:     hiweb_tpl: the PHP compiling template engine
 * File:        smurty_internal_utility.php
 * SVN:         $Id: $
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * For questions, help, comments, discussion, etc., please join the
 * hiweb_tpl mailing list. Send a blank e-mail to
 * smurty-discussion-subscribe@googlegroups.com
 *
 * @link       http://www.smurty.net/
 * @copyright  2008 New Digital Group, Inc.
 * @author     Monte Ohrt <monte at ohrt dot com>
 * @author     Uwe Tews
 * @package    hiweb_tpl
 * @subpackage PluginsInternal
 * @version    3-SVN$Rev: 3286 $
 */

/**
 * Utility class
 *
 * @package    hiweb_tpl
 * @subpackage Security
 */
class Smurty_Internal_Utility
{
    /**
     * private constructor to prevent calls creation of new instances
     */
    final private function __construct()
    {
        // intentionally left blank
    }

    /**
     * Compile all template files
     *
     * @param  string $extension     template file name extension
     * @param  bool   $force_compile force all to recompile
     * @param  int    $time_limit    set maximum execution time
     * @param  int    $max_errors    set maximum allowed errors
     * @param  hiweb_tpl $smurty        hiweb_tpl instance
     *
     * @return integer number of template files compiled
     */
    public static function compileAllTemplates($extension, $force_compile, $time_limit, $max_errors, hiweb_tpl $smurty)
    {
        // switch off time limit
        if (function_exists('set_time_limit')) {
            @set_time_limit($time_limit);
        }
        $smurty->force_compile = $force_compile;
        $_count = 0;
        $_error_count = 0;
        // loop over array of template directories
        foreach ($smurty->getTemplateDir() as $_dir) {
            $_compileDirs = new RecursiveDirectoryIterator($_dir);
            $_compile = new RecursiveIteratorIterator($_compileDirs);
            foreach ($_compile as $_fileinfo) {
                $_file = $_fileinfo->getFilename();
                if (substr(basename($_fileinfo->getPathname()), 0, 1) == '.' || strpos($_file, '.svn') !== false) {
                    continue;
                }
                if (!substr_compare($_file, $extension, - strlen($extension)) == 0) {
                    continue;
                }
                if ($_fileinfo->getPath() == substr($_dir, 0, - 1)) {
                    $_template_file = $_file;
                } else {
                    $_template_file = substr($_fileinfo->getPath(), strlen($_dir)) . DIR_SEPARATOR . $_file;
                }
                echo '<br>', $_dir, '---', $_template_file;
                flush();
                $_start_time = microtime(true);
                try {
                    $_tpl = $smurty->createTemplate($_template_file, null, null, null, false);
                    if ($_tpl->mustCompile()) {
                        $_tpl->compileTemplateSource();
                        $_count ++;
                        echo ' compiled in  ', microtime(true) - $_start_time, ' seconds';
                        flush();
                    } else {
                        echo ' is up to date';
                        flush();
                    }
                }
                catch (Exception $e) {
                    echo 'Error: ', $e->getMessage(), "<br><br>";
                    $_error_count ++;
                }
                // free memory
                $smurty->template_objects = array();
                $_tpl->smurty->template_objects = array();
                $_tpl = null;
                if ($max_errors !== null && $_error_count == $max_errors) {
                    echo '<br><br>too many errors';
                    exit();
                }
            }
        }

        return $_count;
    }

    /**
     * Compile all config files
     *
     * @param  string $extension     config file name extension
     * @param  bool   $force_compile force all to recompile
     * @param  int    $time_limit    set maximum execution time
     * @param  int    $max_errors    set maximum allowed errors
     * @param  hiweb_tpl $smurty        hiweb_tpl instance
     *
     * @return integer number of config files compiled
     */
    public static function compileAllConfig($extension, $force_compile, $time_limit, $max_errors, hiweb_tpl $smurty)
    {
        // switch off time limit
        if (function_exists('set_time_limit')) {
            @set_time_limit($time_limit);
        }
        $smurty->force_compile = $force_compile;
        $_count = 0;
        $_error_count = 0;
        // loop over array of template directories
        foreach ($smurty->getConfigDir() as $_dir) {
            $_compileDirs = new RecursiveDirectoryIterator($_dir);
            $_compile = new RecursiveIteratorIterator($_compileDirs);
            foreach ($_compile as $_fileinfo) {
                $_file = $_fileinfo->getFilename();
                if (substr(basename($_fileinfo->getPathname()), 0, 1) == '.' || strpos($_file, '.svn') !== false) {
                    continue;
                }
                if (!substr_compare($_file, $extension, - strlen($extension)) == 0) {
                    continue;
                }
                if ($_fileinfo->getPath() == substr($_dir, 0, - 1)) {
                    $_config_file = $_file;
                } else {
                    $_config_file = substr($_fileinfo->getPath(), strlen($_dir)) . DIR_SEPARATOR . $_file;
                }
                echo '<br>', $_dir, '---', $_config_file;
                flush();
                $_start_time = microtime(true);
                try {
                    $_config = new Smurty_Internal_Config($_config_file, $smurty);
                    if ($_config->mustCompile()) {
                        $_config->compileConfigSource();
                        $_count ++;
                        echo ' compiled in  ', microtime(true) - $_start_time, ' seconds';
                        flush();
                    } else {
                        echo ' is up to date';
                        flush();
                    }
                }
                catch (Exception $e) {
                    echo 'Error: ', $e->getMessage(), "<br><br>";
                    $_error_count ++;
                }
                if ($max_errors !== null && $_error_count == $max_errors) {
                    echo '<br><br>too many errors';
                    exit();
                }
            }
        }

        return $_count;
    }

    /**
     * Delete compiled template file
     *
     * @param  string  $resource_name template name
     * @param  string  $compile_id    compile id
     * @param  integer $exp_time      expiration time
     * @param  hiweb_tpl  $smurty        hiweb_tpl instance
     *
     * @return integer number of template files deleted
     */
    public static function clearCompiledTemplate($resource_name, $compile_id, $exp_time, hiweb_tpl $smurty)
    {
        $_compile_dir = realpath($smurty->getCompileDir()) . '/';
        $_compile_id = isset($compile_id) ? preg_replace('![^\w\|]+!', '_', $compile_id) : null;
        $_dir_sep = $smurty->use_sub_dirs ? '/' : '^';
        if (isset($resource_name)) {
            $_save_stat = $smurty->caching;
            $smurty->caching = false;
            $tpl = new $smurty->template_class($resource_name, $smurty);
            $smurty->caching = $_save_stat;

            // remove from template cache
            $tpl->source; // have the template registered before unset()
            if ($smurty->allow_ambiguous_resources) {
                $_templateId = $tpl->source->unique_resource . $tpl->cache_id . $tpl->compile_id;
            } else {
                $_templateId = $smurty->joined_template_dir . '#' . $resource_name . $tpl->cache_id . $tpl->compile_id;
            }
            if (isset($_templateId[150])) {
                $_templateId = sha1($_templateId);
            }
            unset($smurty->template_objects[$_templateId]);

            if ($tpl->source->exists) {
                $_resource_part_1 = basename(str_replace('^', '/', $tpl->compiled->filepath));
                $_resource_part_1_length = strlen($_resource_part_1);
            } else {
                return 0;
            }

            $_resource_part_2 = str_replace('.php', '.cache.php', $_resource_part_1);
            $_resource_part_2_length = strlen($_resource_part_2);
        }
        $_dir = $_compile_dir;
        if ($smurty->use_sub_dirs && isset($_compile_id)) {
            $_dir .= $_compile_id . $_dir_sep;
        }
        if (isset($_compile_id)) {
            $_compile_id_part = str_replace('\\', '/', $_compile_dir . $_compile_id . $_dir_sep);
            $_compile_id_part_length = strlen($_compile_id_part);
        }
        $_count = 0;
        try {
            $_compileDirs = new RecursiveDirectoryIterator($_dir);
            // NOTE: UnexpectedValueException thrown for PHP >= 5.3
        }
        catch (Exception $e) {
            return 0;
        }
        $_compile = new RecursiveIteratorIterator($_compileDirs, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($_compile as $_file) {
            if (substr(basename($_file->getPathname()), 0, 1) == '.' || strpos($_file, '.svn') !== false) {
                continue;
            }

            $_filepath = str_replace('\\', '/', (string) $_file);

            if ($_file->isDir()) {
                if (!$_compile->isDot()) {
                    // delete folder if empty
                    @rmdir($_file->getPathname());
                }
            } else {
                $unlink = false;
                if ((!isset($_compile_id) || (isset($_filepath[$_compile_id_part_length]) && $a = !strncmp($_filepath, $_compile_id_part, $_compile_id_part_length)))
                    && (!isset($resource_name)
                        || (isset($_filepath[$_resource_part_1_length])
                            && substr_compare($_filepath, $_resource_part_1, - $_resource_part_1_length, $_resource_part_1_length) == 0)
                        || (isset($_filepath[$_resource_part_2_length])
                            && substr_compare($_filepath, $_resource_part_2, - $_resource_part_2_length, $_resource_part_2_length) == 0))
                ) {
                    if (isset($exp_time)) {
                        if (time() - @filemtime($_filepath) >= $exp_time) {
                            $unlink = true;
                        }
                    } else {
                        $unlink = true;
                    }
                }

                if ($unlink && @unlink($_filepath)) {
                    $_count ++;
                }
            }
        }
        // clear compiled cache
        Smurty_Resource::$sources = array();
        Smurty_Resource::$compileds = array();

        return $_count;
    }

    /**
     * Return array of tag/attributes of all tags used by an template
     *
     * @param Smurty_Internal_Template $template
     *
     * @throws Exception
     * @throws SmurtyException
     * @return array                    of tag/attributes
     */
    public static function getTags(Smurty_Internal_Template $template)
    {
        $template->smurty->get_used_tags = true;
        $template->compileTemplateSource();

        return $template->used_tags;
    }

    /**
     * diagnose hiweb_tpl setup
     * If $errors is secified, the diagnostic report will be appended to the array, rather than being output.
     *
     * @param  hiweb_tpl $smurty hiweb_tpl instance to test
     * @param  array  $errors array to push results into rather than outputting them
     *
     * @return bool   status, true if everything is fine, false else
     */
    public static function testInstall(hiweb_tpl $smurty, &$errors = null)
    {
        $status = true;

        if ($errors === null) {
            echo "<PRE>\n";
            echo "hiweb_tpl Installation test...\n";
            echo "Testing template directory...\n";
        }

        $_stream_resolve_include_path = function_exists('stream_resolve_include_path');

        // test if all registered template_dir are accessible
        foreach ($smurty->getTemplateDir() as $template_dir) {
            $_template_dir = $template_dir;
            $template_dir = realpath($template_dir);
            // resolve include_path or fail existence
            if (!$template_dir) {
                if ($smurty->use_include_path && !preg_match('/^([\/\\\\]|[a-zA-Z]:[\/\\\\])/', $_template_dir)) {
                    // try PHP include_path
                    if ($_stream_resolve_include_path) {
                        $template_dir = stream_resolve_include_path($_template_dir);
                    } else {
                        $template_dir = Smurty_Internal_Get_Include_Path::getIncludePath($_template_dir);
                    }

                    if ($template_dir !== false) {
                        if ($errors === null) {
                            echo "$template_dir is OK.\n";
                        }

                        continue;
                    } else {
                        $status = false;
                        $message = "FAILED: $_template_dir does not exist (and couldn't be found in include_path either)";
                        if ($errors === null) {
                            echo $message . ".\n";
                        } else {
                            $errors['template_dir'] = $message;
                        }

                        continue;
                    }
                } else {
                    $status = false;
                    $message = "FAILED: $_template_dir does not exist";
                    if ($errors === null) {
                        echo $message . ".\n";
                    } else {
                        $errors['template_dir'] = $message;
                    }

                    continue;
                }
            }

            if (!is_dir($template_dir)) {
                $status = false;
                $message = "FAILED: $template_dir is not a directory";
                if ($errors === null) {
                    echo $message . ".\n";
                } else {
                    $errors['template_dir'] = $message;
                }
            } elseif (!is_readable($template_dir)) {
                $status = false;
                $message = "FAILED: $template_dir is not readable";
                if ($errors === null) {
                    echo $message . ".\n";
                } else {
                    $errors['template_dir'] = $message;
                }
            } else {
                if ($errors === null) {
                    echo "$template_dir is OK.\n";
                }
            }
        }

        if ($errors === null) {
            echo "Testing compile directory...\n";
        }

        // test if registered compile_dir is accessible
        $__compile_dir = $smurty->getCompileDir();
        $_compile_dir = realpath($__compile_dir);
        if (!$_compile_dir) {
            $status = false;
            $message = "FAILED: {$__compile_dir} does not exist";
            if ($errors === null) {
                echo $message . ".\n";
            } else {
                $errors['compile_dir'] = $message;
            }
        } elseif (!is_dir($_compile_dir)) {
            $status = false;
            $message = "FAILED: {$_compile_dir} is not a directory";
            if ($errors === null) {
                echo $message . ".\n";
            } else {
                $errors['compile_dir'] = $message;
            }
        } elseif (!is_readable($_compile_dir)) {
            $status = false;
            $message = "FAILED: {$_compile_dir} is not readable";
            if ($errors === null) {
                echo $message . ".\n";
            } else {
                $errors['compile_dir'] = $message;
            }
        } elseif (!is_writable($_compile_dir)) {
            $status = false;
            $message = "FAILED: {$_compile_dir} is not writable";
            if ($errors === null) {
                echo $message . ".\n";
            } else {
                $errors['compile_dir'] = $message;
            }
        } else {
            if ($errors === null) {
                echo "{$_compile_dir} is OK.\n";
            }
        }

        if ($errors === null) {
            echo "Testing plugins directory...\n";
        }

        // test if all registered plugins_dir are accessible
        // and if core plugins directory is still registered
        $_core_plugins_dir = realpath(dirname(__FILE__) . '/../plugins');
        $_core_plugins_available = false;
        foreach ($smurty->getPluginsDir() as $plugin_dir) {
            $_plugin_dir = $plugin_dir;
            $plugin_dir = realpath($plugin_dir);
            // resolve include_path or fail existence
            if (!$plugin_dir) {
                if ($smurty->use_include_path && !preg_match('/^([\/\\\\]|[a-zA-Z]:[\/\\\\])/', $_plugin_dir)) {
                    // try PHP include_path
                    if ($_stream_resolve_include_path) {
                        $plugin_dir = stream_resolve_include_path($_plugin_dir);
                    } else {
                        $plugin_dir = Smurty_Internal_Get_Include_Path::getIncludePath($_plugin_dir);
                    }

                    if ($plugin_dir !== false) {
                        if ($errors === null) {
                            echo "$plugin_dir is OK.\n";
                        }

                        continue;
                    } else {
                        $status = false;
                        $message = "FAILED: $_plugin_dir does not exist (and couldn't be found in include_path either)";
                        if ($errors === null) {
                            echo $message . ".\n";
                        } else {
                            $errors['plugins_dir'] = $message;
                        }

                        continue;
                    }
                } else {
                    $status = false;
                    $message = "FAILED: $_plugin_dir does not exist";
                    if ($errors === null) {
                        echo $message . ".\n";
                    } else {
                        $errors['plugins_dir'] = $message;
                    }

                    continue;
                }
            }

            if (!is_dir($plugin_dir)) {
                $status = false;
                $message = "FAILED: $plugin_dir is not a directory";
                if ($errors === null) {
                    echo $message . ".\n";
                } else {
                    $errors['plugins_dir'] = $message;
                }
            } elseif (!is_readable($plugin_dir)) {
                $status = false;
                $message = "FAILED: $plugin_dir is not readable";
                if ($errors === null) {
                    echo $message . ".\n";
                } else {
                    $errors['plugins_dir'] = $message;
                }
            } elseif ($_core_plugins_dir && $_core_plugins_dir == realpath($plugin_dir)) {
                $_core_plugins_available = true;
                if ($errors === null) {
                    echo "$plugin_dir is OK.\n";
                }
            } else {
                if ($errors === null) {
                    echo "$plugin_dir is OK.\n";
                }
            }
        }
        if (!$_core_plugins_available) {
            $status = false;
            $message = "WARNING: hiweb_tpl's own libs/plugins is not available";
            if ($errors === null) {
                echo $message . ".\n";
            } elseif (!isset($errors['plugins_dir'])) {
                $errors['plugins_dir'] = $message;
            }
        }

        if ($errors === null) {
            echo "Testing cache directory...\n";
        }

        // test if all registered cache_dir is accessible
        $__cache_dir = $smurty->getCacheDir();
        $_cache_dir = realpath($__cache_dir);
        if (!$_cache_dir) {
            $status = false;
            $message = "FAILED: {$__cache_dir} does not exist";
            if ($errors === null) {
                echo $message . ".\n";
            } else {
                $errors['cache_dir'] = $message;
            }
        } elseif (!is_dir($_cache_dir)) {
            $status = false;
            $message = "FAILED: {$_cache_dir} is not a directory";
            if ($errors === null) {
                echo $message . ".\n";
            } else {
                $errors['cache_dir'] = $message;
            }
        } elseif (!is_readable($_cache_dir)) {
            $status = false;
            $message = "FAILED: {$_cache_dir} is not readable";
            if ($errors === null) {
                echo $message . ".\n";
            } else {
                $errors['cache_dir'] = $message;
            }
        } elseif (!is_writable($_cache_dir)) {
            $status = false;
            $message = "FAILED: {$_cache_dir} is not writable";
            if ($errors === null) {
                echo $message . ".\n";
            } else {
                $errors['cache_dir'] = $message;
            }
        } else {
            if ($errors === null) {
                echo "{$_cache_dir} is OK.\n";
            }
        }

        if ($errors === null) {
            echo "Testing configs directory...\n";
        }

        // test if all registered config_dir are accessible
        foreach ($smurty->getConfigDir() as $config_dir) {
            $_config_dir = $config_dir;
            $config_dir = realpath($config_dir);
            // resolve include_path or fail existence
            if (!$config_dir) {
                if ($smurty->use_include_path && !preg_match('/^([\/\\\\]|[a-zA-Z]:[\/\\\\])/', $_config_dir)) {
                    // try PHP include_path
                    if ($_stream_resolve_include_path) {
                        $config_dir = stream_resolve_include_path($_config_dir);
                    } else {
                        $config_dir = Smurty_Internal_Get_Include_Path::getIncludePath($_config_dir);
                    }

                    if ($config_dir !== false) {
                        if ($errors === null) {
                            echo "$config_dir is OK.\n";
                        }

                        continue;
                    } else {
                        $status = false;
                        $message = "FAILED: $_config_dir does not exist (and couldn't be found in include_path either)";
                        if ($errors === null) {
                            echo $message . ".\n";
                        } else {
                            $errors['config_dir'] = $message;
                        }

                        continue;
                    }
                } else {
                    $status = false;
                    $message = "FAILED: $_config_dir does not exist";
                    if ($errors === null) {
                        echo $message . ".\n";
                    } else {
                        $errors['config_dir'] = $message;
                    }

                    continue;
                }
            }

            if (!is_dir($config_dir)) {
                $status = false;
                $message = "FAILED: $config_dir is not a directory";
                if ($errors === null) {
                    echo $message . ".\n";
                } else {
                    $errors['config_dir'] = $message;
                }
            } elseif (!is_readable($config_dir)) {
                $status = false;
                $message = "FAILED: $config_dir is not readable";
                if ($errors === null) {
                    echo $message . ".\n";
                } else {
                    $errors['config_dir'] = $message;
                }
            } else {
                if ($errors === null) {
                    echo "$config_dir is OK.\n";
                }
            }
        }

        if ($errors === null) {
            echo "Testing sysplugin files...\n";
        }
        // test if hiweb-core-tpl are available
        $source = HIWEB_CORE_HTML_ASSETS;
        if (is_dir($source)) {
            $expected = array(
                "smurty_cacheresource.php"                                  => true,
                "smurty_cacheresource_custom.php"                           => true,
                "smurty_cacheresource_keyvaluestore.php"                    => true,
                "smurty_config_source.php"                                  => true,
                "smurty_internal_cacheresource_file.php"                    => true,
                "smurty_internal_compile_append.php"                        => true,
                "smurty_internal_compile_assign.php"                        => true,
                "smurty_internal_compile_block.php"                         => true,
                "smurty_internal_compile_break.php"                         => true,
                "smurty_internal_compile_call.php"                          => true,
                "smurty_internal_compile_capture.php"                       => true,
                "smurty_internal_compile_config_load.php"                   => true,
                "smurty_internal_compile_continue.php"                      => true,
                "smurty_internal_compile_debug.php"                         => true,
                "smurty_internal_compile_eval.php"                          => true,
                "smurty_internal_compile_extends.php"                       => true,
                "smurty_internal_compile_for.php"                           => true,
                "smurty_internal_compile_foreach.php"                       => true,
                "smurty_internal_compile_function.php"                      => true,
                "smurty_internal_compile_if.php"                            => true,
                "smurty_internal_compile_include.php"                       => true,
                "smurty_internal_compile_include_php.php"                   => true,
                "smurty_internal_compile_insert.php"                        => true,
                "smurty_internal_compile_ldelim.php"                        => true,
                "smurty_internal_compile_nocache.php"                       => true,
                "smurty_internal_compile_private_block_plugin.php"          => true,
                "smurty_internal_compile_private_function_plugin.php"       => true,
                "smurty_internal_compile_private_modifier.php"              => true,
                "smurty_internal_compile_private_object_block_function.php" => true,
                "smurty_internal_compile_private_object_function.php"       => true,
                "smurty_internal_compile_private_print_expression.php"      => true,
                "smurty_internal_compile_private_registered_block.php"      => true,
                "smurty_internal_compile_private_registered_function.php"   => true,
                "smurty_internal_compile_private_special_variable.php"      => true,
                "smurty_internal_compile_rdelim.php"                        => true,
                "smurty_internal_compile_section.php"                       => true,
                "smurty_internal_compile_setfilter.php"                     => true,
                "smurty_internal_compile_while.php"                         => true,
                "smurty_internal_compilebase.php"                           => true,
                "smurty_internal_config.php"                                => true,
                "smurty_internal_config_file_compiler.php"                  => true,
                "smurty_internal_configfilelexer.php"                       => true,
                "smurty_internal_configfileparser.php"                      => true,
                "smurty_internal_data.php"                                  => true,
                "smurty_internal_debug.php"                                 => true,
                "smurty_internal_filter_handler.php"                        => true,
                "smurty_internal_function_call_handler.php"                 => true,
                "smurty_internal_get_include_path.php"                      => true,
                "smurty_internal_nocache_insert.php"                        => true,
                "smurty_internal_parsetree.php"                             => true,
                "smurty_internal_resource_eval.php"                         => true,
                "smurty_internal_resource_extends.php"                      => true,
                "smurty_internal_resource_file.php"                         => true,
                "smurty_internal_resource_registered.php"                   => true,
                "smurty_internal_resource_stream.php"                       => true,
                "smurty_internal_resource_string.php"                       => true,
                "smurty_internal_smurtytemplatecompiler.php"                => true,
                "smurty_internal_template.php"                              => true,
                "smurty_internal_templatebase.php"                          => true,
                "smurty_internal_templatecompilerbase.php"                  => true,
                "smurty_internal_templatelexer.php"                         => true,
                "smurty_internal_templateparser.php"                        => true,
                "smurty_internal_utility.php"                               => true,
                "smurty_internal_write_file.php"                            => true,
                "smurty_resource.php"                                       => true,
                "smurty_resource_custom.php"                                => true,
                "smurty_resource_recompiled.php"                            => true,
                "smurty_resource_uncompiled.php"                            => true,
                "smurty_security.php"                                       => true,
            );
            $iterator = new DirectoryIterator($source);
            foreach ($iterator as $file) {
                if (!$file->isDot()) {
                    $filename = $file->getFilename();
                    if (isset($expected[$filename])) {
                        unset($expected[$filename]);
                    }
                }
            }
            if ($expected) {
                $status = false;
                $message = "FAILED: files missing from libs/hiweb-core-tpl: " . join(', ', array_keys($expected));
                if ($errors === null) {
                    echo $message . ".\n";
                } else {
                    $errors['hiweb-core-tpl'] = $message;
                }
            } elseif ($errors === null) {
                echo "... OK\n";
            }
        } else {
            $status = false;
            $message = "FAILED: " . HIWEB_CORE_HTML_ASSETS . ' is not a directory';
            if ($errors === null) {
                echo $message . ".\n";
            } else {
                $errors['sysplugins_dir_constant'] = $message;
            }
        }

        if ($errors === null) {
            echo "Testing plugin files...\n";
        }
        // test if core plugins are available
        $source = HIWEB_TPL_PLUGINS_DIR;
        if (is_dir($source)) {
            $expected = array(
                "block.textformat.php"                  => true,
                "function.counter.php"                  => true,
                "function.cycle.php"                    => true,
                "function.fetch.php"                    => true,
                "function.html_checkboxes.php"          => true,
                "function.html_image.php"               => true,
                "function.html_options.php"             => true,
                "function.html_radios.php"              => true,
                "function.html_select_date.php"         => true,
                "function.html_select_time.php"         => true,
                "function.html_table.php"               => true,
                "function.mailto.php"                   => true,
                "function.math.php"                     => true,
                "modifier.capitalize.php"               => true,
                "modifier.date_format.php"              => true,
                "modifier.debug_print_var.php"          => true,
                "modifier.escape.php"                   => true,
                "modifier.regex_replace.php"            => true,
                "modifier.replace.php"                  => true,
                "modifier.spacify.php"                  => true,
                "modifier.truncate.php"                 => true,
                "modifiercompiler.cat.php"              => true,
                "modifiercompiler.count_characters.php" => true,
                "modifiercompiler.count_paragraphs.php" => true,
                "modifiercompiler.count_sentences.php"  => true,
                "modifiercompiler.count_words.php"      => true,
                "modifiercompiler.default.php"          => true,
                "modifiercompiler.escape.php"           => true,
                "modifiercompiler.from_charset.php"     => true,
                "modifiercompiler.indent.php"           => true,
                "modifiercompiler.lower.php"            => true,
                "modifiercompiler.noprint.php"          => true,
                "modifiercompiler.string_format.php"    => true,
                "modifiercompiler.strip.php"            => true,
                "modifiercompiler.strip_tags.php"       => true,
                "modifiercompiler.to_charset.php"       => true,
                "modifiercompiler.unescape.php"         => true,
                "modifiercompiler.upper.php"            => true,
                "modifiercompiler.wordwrap.php"         => true,
                "outputfilter.trimwhitespace.php"       => true,
                "shared.escape_special_chars.php"       => true,
                "shared.literal_compiler_param.php"     => true,
                "shared.make_timestamp.php"             => true,
                "shared.mb_str_replace.php"             => true,
                "shared.mb_unicode.php"                 => true,
                "shared.mb_wordwrap.php"                => true,
                "variablefilter.htmlspecialchars.php"   => true,
            );
            $iterator = new DirectoryIterator($source);
            foreach ($iterator as $file) {
                if (!$file->isDot()) {
                    $filename = $file->getFilename();
                    if (isset($expected[$filename])) {
                        unset($expected[$filename]);
                    }
                }
            }
            if ($expected) {
                $status = false;
                $message = "FAILED: files missing from libs/plugins: " . join(', ', array_keys($expected));
                if ($errors === null) {
                    echo $message . ".\n";
                } else {
                    $errors['plugins'] = $message;
                }
            } elseif ($errors === null) {
                echo "... OK\n";
            }
        } else {
            $status = false;
            $message = "FAILED: " . HIWEB_TPL_PLUGINS_DIR . ' is not a directory';
            if ($errors === null) {
                echo $message . ".\n";
            } else {
                $errors['plugins_dir_constant'] = $message;
            }
        }

        if ($errors === null) {
            echo "Tests complete.\n";
            echo "</PRE>\n";
        }

        return $status;
    }
}
