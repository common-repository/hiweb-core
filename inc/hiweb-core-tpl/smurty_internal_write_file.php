<?php
/**
 * hiweb_tpl write file plugin
 *
 * @package    hiweb_tpl
 * @subpackage PluginsInternal
 * @author     Monte Ohrt
 */

/**
 * hiweb_tpl Internal Write File Class
 *
 * @package    hiweb_tpl
 * @subpackage PluginsInternal
 */
class Smurty_Internal_Write_File
{
    /**
     * Writes file in a safe way to disk
     *
     * @param  string $_filepath complete filepath
     * @param  string $_contents file content
     * @param  hiweb_tpl $smurty    smurty instance
     *
     * @throws SmurtyException
     * @return boolean true
     */
    public static function writeFile($_filepath, $_contents, hiweb_tpl $smurty)
    {
        $_error_reporting = error_reporting();
        error_reporting($_error_reporting & ~E_NOTICE & ~E_WARNING);
        if ($smurty->_file_perms !== null) {
            $old_umask = umask(0);
        }

        $_dirpath = dirname($_filepath);
        // if subdirs, create dir structure
        if ($_dirpath !== '.' && !file_exists($_dirpath)) {
            mkdir($_dirpath, $smurty->_dir_perms === null ? 0777 : $smurty->_dir_perms, true);
        }

        // write to tmp file, then move to overt file lock race condition
        $_tmp_file = $_dirpath . DIR_SEPARATOR . str_replace(array('.', ','), '_', uniqid('wrt', true));
        if (!file_put_contents($_tmp_file, $_contents)) {
            error_reporting($_error_reporting);
            throw new SmurtyException("unable to write file {$_tmp_file}");
       }

        /*
         * Windows' rename() fails if the destination exists,
         * Linux' rename() properly handles the overwrite.
         * Simply unlink()ing a file might cause other processes
         * currently reading that file to fail, but linux' rename()
         * seems to be smart enough to handle that for us.
         */
        if (hiweb_tpl::$_IS_WINDOWS) {
            // remove original file
            @unlink($_filepath);
            // rename tmp file
            $success = @rename($_tmp_file, $_filepath);
        } else {
            // rename tmp file
            $success = @rename($_tmp_file, $_filepath);
            if (!$success) {
                // remove original file
                @unlink($_filepath);
                // rename tmp file
                $success = @rename($_tmp_file, $_filepath);
            }
        }

        if (!$success) {
            error_reporting($_error_reporting);
            throw new SmurtyException("unable to write file {$_filepath}");
        }

        if ($smurty->_file_perms !== null) {
            // set file permissions
            chmod($_filepath, $smurty->_file_perms);
            umask($old_umask);
        }
        error_reporting($_error_reporting);

        return true;
    }
}
