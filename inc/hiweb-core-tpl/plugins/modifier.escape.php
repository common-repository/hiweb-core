<?php
/**
 * hiweb_tpl plugin
 *
 * @package    hiweb_tpl
 * @subpackage PluginsModifier
 */

/**
 * hiweb_tpl escape modifier plugin
 * Type:     modifier<br>
 * Name:     escape<br>
 * Purpose:  escape string for output
 *
 * @link   http://www.smurty.net/docs/en/language.modifier.escape
 * @author Monte Ohrt <monte at ohrt dot com>
 *
 * @param string  $string        input string
 * @param string  $esc_type      escape type
 * @param string  $char_set      character set, used for htmlspecialchars() or htmlentities()
 * @param boolean $double_encode encode already encoded entitites again, used for htmlspecialchars() or htmlentities()
 *
 * @return string escaped input string
 */
function smurty_modifier_escape($string, $esc_type = 'html', $char_set = null, $double_encode = true)
{
    static $_double_encode = null;
    if ($_double_encode === null) {
        $_double_encode = version_compare(PHP_VERSION, '5.2.3', '>=');
    }

    if (!$char_set) {
        $char_set = hiweb_tpl::$_CHARSET;
    }

    switch ($esc_type) {
        case 'html':
            if ($_double_encode) {
                // php >=5.3.2 - go native
                return htmlspecialchars($string, ENT_QUOTES, $char_set, $double_encode);
            } else {
                if ($double_encode) {
                    // php <5.2.3 - only handle double encoding
                    return htmlspecialchars($string, ENT_QUOTES, $char_set);
                } else {
                    // php <5.2.3 - prevent double encoding
                    $string = preg_replace('!&(#?\w+);!', '%%%SMURTY_START%%%\\1%%%SMURTY_END%%%', $string);
                    $string = htmlspecialchars($string, ENT_QUOTES, $char_set);
                    $string = str_replace(array('%%%SMURTY_START%%%', '%%%SMURTY_END%%%'), array('&', ';'), $string);

                    return $string;
                }
            }

        case 'htmlall':
            if (hiweb_tpl::$_MBSTRING) {
                // mb_convert_encoding ignores htmlspecialchars()
                if ($_double_encode) {
                    // php >=5.3.2 - go native
                    $string = htmlspecialchars($string, ENT_QUOTES, $char_set, $double_encode);
                } else {
                    if ($double_encode) {
                        // php <5.2.3 - only handle double encoding
                        $string = htmlspecialchars($string, ENT_QUOTES, $char_set);
                    } else {
                        // php <5.2.3 - prevent double encoding
                        $string = preg_replace('!&(#?\w+);!', '%%%SMURTY_START%%%\\1%%%SMURTY_END%%%', $string);
                        $string = htmlspecialchars($string, ENT_QUOTES, $char_set);
                        $string = str_replace(array('%%%SMURTY_START%%%', '%%%SMURTY_END%%%'), array('&', ';'), $string);

                        return $string;
                    }
                }

                // htmlentities() won't convert everything, so use mb_convert_encoding
                return mb_convert_encoding($string, 'HTML-ENTITIES', $char_set);
            }

            // no MBString fallback
            if ($_double_encode) {
                return htmlentities($string, ENT_QUOTES, $char_set, $double_encode);
            } else {
                if ($double_encode) {
                    return htmlentities($string, ENT_QUOTES, $char_set);
                } else {
                    $string = preg_replace('!&(#?\w+);!', '%%%SMURTY_START%%%\\1%%%SMURTY_END%%%', $string);
                    $string = htmlentities($string, ENT_QUOTES, $char_set);
                    $string = str_replace(array('%%%SMURTY_START%%%', '%%%SMURTY_END%%%'), array('&', ';'), $string);

                    return $string;
                }
            }

        case 'url':
            return rawurlencode($string);

        case 'urlpathinfo':
            return str_replace('%2F', '/', rawurlencode($string));

        case 'quotes':
            // escape unescaped single quotes
            return preg_replace("%(?<!\\\\)'%", "\\'", $string);

        case 'hex':
            // escape every byte into hex
            // Note that the UTF-8 encoded character ä will be represented as %c3%a4
            $return = '';
            $_length = strlen($string);
            for ($x = 0; $x < $_length; $x ++) {
                $return .= '%' . bin2hex($string[$x]);
            }

            return $return;

        case 'hexentity':
            $return = '';
            if (hiweb_tpl::$_MBSTRING) {
                require_once( HIWEB_TPL_PLUGINS_DIR . 'shared.mb_unicode.php' );
                $return = '';
                foreach (smurty_mb_to_unicode($string, hiweb_tpl::$_CHARSET) as $unicode) {
                    $return .= '&#x' . strtoupper(dechex($unicode)) . ';';
                }

                return $return;
            }
            // no MBString fallback
            $_length = strlen($string);
            for ($x = 0; $x < $_length; $x ++) {
                $return .= '&#x' . bin2hex($string[$x]) . ';';
            }

            return $return;

        case 'decentity':
            $return = '';
            if (hiweb_tpl::$_MBSTRING) {
                require_once( HIWEB_TPL_PLUGINS_DIR . 'shared.mb_unicode.php' );
                $return = '';
                foreach (smurty_mb_to_unicode($string, hiweb_tpl::$_CHARSET) as $unicode) {
                    $return .= '&#' . $unicode . ';';
                }

                return $return;
            }
            // no MBString fallback
            $_length = strlen($string);
            for ($x = 0; $x < $_length; $x ++) {
                $return .= '&#' . ord($string[$x]) . ';';
            }

            return $return;

        case 'javascript':
            // escape quotes and backslashes, newlines, etc.
            return strtr($string, array('\\' => '\\\\', "'" => "\\'", '"' => '\\"', "\r" => '\\r', "\n" => '\\n', '</' => '<\/'));

        case 'mail':
            if (hiweb_tpl::$_MBSTRING) {
                require_once( HIWEB_TPL_PLUGINS_DIR . 'shared.mb_str_replace.php' );

                return smurty_mb_str_replace(array('@', '.'), array(' [AT] ', ' [DOT] '), $string);
            }
            // no MBString fallback
            return str_replace(array('@', '.'), array(' [AT] ', ' [DOT] '), $string);

        case 'nonstd':
            // escape non-standard chars, such as ms document quotes
            $return = '';
            if (hiweb_tpl::$_MBSTRING) {
                require_once( HIWEB_TPL_PLUGINS_DIR . 'shared.mb_unicode.php' );
                foreach (smurty_mb_to_unicode($string, hiweb_tpl::$_CHARSET) as $unicode) {
                    if ($unicode >= 126) {
                        $return .= '&#' . $unicode . ';';
                    } else {
                        $return .= chr($unicode);
                    }
                }

                return $return;
            }

            $_length = strlen($string);
            for ($_i = 0; $_i < $_length; $_i ++) {
                $_ord = ord(substr($string, $_i, 1));
                // non-standard char, escape it
                if ($_ord >= 126) {
                    $return .= '&#' . $_ord . ';';
                } else {
                    $return .= substr($string, $_i, 1);
                }
            }

            return $return;

        default:
            return $string;
    }
}
