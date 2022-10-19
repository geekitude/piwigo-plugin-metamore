<?php
// +-----------------------------------------------------------------------+
// | Piwigo - a PHP based photo gallery                                    |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2008-2022 Piwigo Team                  http://piwigo.org |
// | Copyright(C) 2003-2008 PhpWebGallery team    http://phpwebgallery.net |
// | Copyright(C) 2002-2003 Pierrick LE GALL   http://le-gall.net/pierrick |
// +-----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify  |
// | it under the terms of the GNU General Public License as published by  |
// | the Free Software Foundation                                          |
// |                                                                       |
// | This program is distributed in the hope that it will be useful, but   |
// | WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU      |
// | General Public License for more details.                              |
// |                                                                       |
// | You should have received a copy of the GNU General Public License     |
// | along with this program; if not, write to the Free Software           |
// | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, |
// | USA.                                                                  |
// +-----------------------------------------------------------------------+

/*
Plugin Name: MetaMore
Version: 13.a
Description: Get more out of your metadata
Plugin URI: https://github.com/geekitude/piwigo-plugin-metamore
Author: Geekitude
Author URI: ???
Author: Geekitude
Has Settings: false
*/

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

add_event_handler('picture_pictures_data', 'metamore_data');
function metamore_data($content)
{
    global $picture, $metamore_exif, $metamore_permalinks, $metamore_tooltips, $lang;

    $metamore_exif = exif_read_data($picture['current']['path'], 'EXIF');

    $metamore_permalinks = metamore_permalinks_build();

    $metamore_tooltips = array();
    $metamore_tooltips['make'] = (isset($lang['exif_field_Make']) ? $lang['exif_field_Make'] : "Make");
    $metamore_tooltips['model'] = (isset($lang['exif_field_Model']) ? $lang['exif_field_Model'] : "Model");
    $metamore_tooltips['lenstype'] = (isset($lang['iptc_lenstype']) ? $lang['iptc_lenstype'] : "Lense");
    $metamore_tooltips['lens35'] = (isset($lang['iptc_lens35']) ? $lang['iptc_lens35'] : "Focal Length (35mm)");
    $metamore_tooltips['fnumber'] = (isset($lang['exif_field_FNumber']) ? $lang['exif_field_FNumber'] : "Aperture");
    $metamore_tooltips['time'] = (isset($lang['exif_field_ExposureTime']) ? $lang['exif_field_ExposureTime'] : "Exposure Time");
    $metamore_tooltips['iso'] = (isset($lang['exif_field_ISOSpeedRatings']) ? $lang['exif_field_ISOSpeedRatings'] : "ISO");
    $metamore_tooltips['exposurebias'] = (isset($lang['exif_field_ExposureBiasValue']) ? $lang['exif_field_ExposureBiasValue'] : "Exposure Compensation");
    $metamore_tooltips['flash'] = (isset($lang['exif_field_Flash']) ? $lang['exif_field_Flash'] : "Flash");

    return $content;
}

function metamore_permalinks_build()
{
    global $picture, $metamore_exif;

    $return = array();

    // author
    if (!empty($picture['current']['author']))
    {
        $str = htmlentities($picture['current']['author'], ENT_NOQUOTES, 'utf-8');
        $str = preg_replace('#&([A-za-z])(?:uml|circ|tilde|acute|grave|cedil|ring);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
        $str = preg_replace('#&[^;]+;#', '', $str);
        $str = strtr($str, array('  ' => '_', ' ' => '_', '.' => ''));
        $return['author'] = $str;
    }

    // lenstype
    if (!empty($picture['current']['lenstype']))
    {
        $return['lenstype'] = strtr($picture['current']['lenstype'], array(' ' => '_', '.' => ''));
    }

    // lens35
    if (!empty($picture['current']['lens35']))
    {
        if ($picture['current']['lens35']>0 && $picture['current']['lens35']<24)
        {
            $return['lens35'] = "Lens35UWA";
        } elseif ($picture['current']['lens35']>=24 && $picture['current']['lens35']<35)
        {
            $return['lens35'] = "Lens35WA";
        } elseif ($picture['current']['lens35']>=35 && $picture['current']['lens35']<70)
        {
            $return['lens35'] = "Lens35STD";
        } elseif ($picture['current']['lens35']>=70 && $picture['current']['lens35']<300)
        {
            $return['lens35'] = "Lens35TELE";
        } elseif ($picture['current']['lens35']>300)
        {
            $return['lens35'] = "Lens35SUPER";
        } else
        {
            $return['lens35'] = null;
        }
    }

    // make
    if (!empty($metamore_exif['Make']))
    {
        $return['make'] = $metamore_exif['Make'];
    }

    // model
    if (!empty($metamore_exif['Model']))
    {
        $return['model'] = $metamore_exif['Model'];
    }

    return $return;
}

add_event_handler('loc_end_picture', 'metamore_assign');
function metamore_assign()
{
    global $template, $picture, $metamore_exif, $metamore_permalinks, $metamore_tooltips;

    if (isset($picture['current']['is_gvideo']) and $picture['current']['is_gvideo'])
    {
        return;
    }

    $swap = array();
    if (file_exists('./local/metamore/swap.php'))
    {
        include('./local/metamore/swap.php');
    }
    if ((isset($metamore_exif['Make'])) && (!isset($swap[$metamore_exif['Make']])))
    {
        $swap[$metamore_exif['Make']] = $metamore_exif['Make'];
    }
    if ((isset($metamore_exif['Model'])) && (!isset($swap[$metamore_exif['Model']])))
    {
        $swap[$metamore_exif['Model']] = $metamore_exif['Model'];
    }
    if ((isset($picture['current']['lenstype'])) && (!isset($swap[$picture['current']['lenstype']])))
    {
        $swap[$picture['current']['lenstype']] = $picture['current']['lenstype'];
    }

    $template->set_prefilter('picture', 'metamore_prefilter');

    $metamore_hardware = null;
    // make & model
    $make = null;
    $model = null;
    $tooltip = null;
    $extraclass = null;
    if (isset($metamore_exif['Make']) && ($metamore_exif['Make'] != null))
    {
        $make .= '<a href="http://localhost:88/index.php?/category/'.$metamore_permalinks['make'].'" title="'.$metamore_tooltips['make'].'">'.$swap[$metamore_exif['Make']].'</a>';
    }
    if (isset($metamore_exif['Model']) && ($metamore_exif['Model'] != null))
    {
        $model .= '<a href="http://localhost:88/index.php?/category/'.$metamore_permalinks['model'].'" title="'.$metamore_tooltips['model'].'">'.$swap[$metamore_exif['Model']].'</a>';
        if (in_array($metamore_exif['Model'], array("SM-G970F", "blah","blahblah")))
        {
            $extraclass = " phone";
        } elseif (in_array($metamore_exif['Model'], array("COOLPIX S600","FZ-200")))
        {
            $extraclass = " compact";
        } elseif (in_array($metamore_exif['Model'], array("bouh","bouhbouh")))
        {
            $extraclass = " slr";
        }
    }
    if ($make!=null || $model!=null)
    {
        if (isset($metamore_tooltips['make']))
        {
            $tooltip = $metamore_tooltips['make'];
        }
        if (isset($metamore_tooltips['model']))
        {
            $tooltip .= " & ".$metamore_tooltips['model'];
        }
        $tooltip=ltrim($tooltip, " & ");
        $metamore_hardware .= '<div title="'.$tooltip.'"><span class="meta camera'.$extraclass.'"><img src="./plugins/metamore/images/placeholder.png"></span>'.$make.$model.'</div>';
    }
    // lenstype
    if (isset($picture['current']['lenstype']) && ($picture['current']['lenstype'] != null))
    {
        $metamore_hardware .= '<div title="'.$metamore_tooltips['lenstype'].'"><span class="meta lenstype"><img src="./plugins/metamore/images/placeholder.png"></span><a href="http://localhost:88/index.php?/category/'.$metamore_permalinks['lenstype'].'">'.$swap[$picture['current']['lenstype']].'</a></div>';
    }
    // lens35
    if (isset($picture['current']['lens35']) && ($picture['current']['lens35'] > 0))
    {
        $metamore_hardware .= '<div title="'.$metamore_tooltips['lens35'].'"><span class="meta lens35"><img src="./plugins/metamore/images/placeholder.png"></span><a href="http://localhost:88/index.php?/category/'.$metamore_permalinks['lens35'].'">'.$picture['current']['lens35'].'&#xA0;mm</a></div>';
    }

    $metamore_settings = null;
    // aperture
    if (isset($metamore_exif['COMPUTED']['ApertureFNumber']) && ($metamore_exif['COMPUTED']['ApertureFNumber'] != null)) {
        $metamore_settings .= '<div title="'.$metamore_tooltips['fnumber'].'"><span class="meta aperture"><img src="./plugins/metamore/images/placeholder.png"></span>'.$metamore_exif['COMPUTED']['ApertureFNumber'].'</div>';
    }
    // exposuretime
    if (isset($metamore_exif['ExposureTime']) && ($metamore_exif['ExposureTime'] != null))
    {
        $tokens = explode('/', $metamore_exif['ExposureTime']);
        if (isset($tokens[1]))
        {
            if ($tokens[0] == 0)
            {
                $value = 0;
            } elseif ($tokens[1] > 0)
            {
                if ($tokens[1] == 1)
                {
                    $value = $tokens[0];
                }
                while ($tokens[0] % 10 == 0)
                {
                    $tokens[0] = $tokens[0] / 10;
                    $tokens[1] = $tokens[1] / 10;
                }
                if ($tokens[1] == 1)
                {
                    $value = $tokens[0];
                } else
                {
                    $value = '1/'.floor(1/($tokens[0]/$tokens[1]));
                }
            } else
            {
                $value = $tokens[0];
            }
        } else
        {
            $value = $metamore_exif['ExposureTime'];
        }
        $metamore_settings .= '<div title="'.$metamore_tooltips['time'].'"><span class="meta time"><img src="./plugins/metamore/images/placeholder.png"></span>'.$value.'&#xA0;s</div>';
    }
    // iso
    if (isset($metamore_exif['ISOSpeedRatings']) && ($metamore_exif['ISOSpeedRatings'] != null)) {
        $metamore_settings .= '<div title="'.$metamore_tooltips['iso'].'"><span class="meta iso"><img src="./plugins/metamore/images/placeholder.png"></span>'.$metamore_exif['ISOSpeedRatings'].'</div>';
    }
    // exposure compensation
    if (isset($metamore_exif['ExposureBiasValue']) && ($metamore_exif['ExposureBiasValue'] != null)) {
        $tokens = explode('/', $metamore_exif['ExposureBiasValue']);
        if (0 == $tokens[1])
        {
            $value = ($tokens[0] < 0 ? '-' : '+').'&infin;';
        }
        $value = sprintf('%.1f', $tokens[0] / $tokens[1]);
        $metamore_settings .= '<div title="'.$metamore_tooltips['exposurebias'].'"><span class="meta exposurebias"><img src="./plugins/metamore/images/placeholder.png"></span>'.$value.'&#xA0;EV</div>';
    }
    // flash
    if (isset($metamore_exif['Flash'])) {
        $extraclass = null;
        $retValue = null;
        $value = (int)$metamore_exif['Flash'];
        // 1st bit is fired/did not fired
        if (($value & 1) > 0)
        {
            //$retValue = l10n('yes');
        } else
        {
            //$retValue = l10n('no');
            $extraclass = " no";
        }
        // 2nd+3rd bits are return light mode
        $returnLight = $value & (3 << 1);
        switch ($returnLight)
        {
//            case 2 << 1: $retValue .= ', '.l10n('exif_value_flash_return_light_not_detected');break;
//            case 3 << 1: $retValue .= ', '.l10n('exif_value_flash_return_light_detected');break;
            case 2 << 1: $retValue = l10n('exif_value_flash_return_light_not_detected');break;
            case 3 << 1: $retValue = l10n('exif_value_flash_return_light_detected');break;
        }
        // 4th+5th bits are mode
        $mode = $value & (3 << 3);
        switch ($mode)
        {
            case 0: $retValue .= $extraclass!=" no" ? ', '.l10n('exif_value_flash_mode').': '.l10n('exif_value_flash_mode_unknown') : "";break;
            case 1 << 3: $retValue .= ', '.l10n('exif_value_flash_mode').': '.l10n('exif_value_flash_mode_compulsory');break;
            case 2 << 3: $retValue .= ', '.l10n('exif_value_flash_mode').': '.l10n('exif_value_flash_mode_supress');break;
            //case 3 << 3: $retValue .= ', '.l10n('exif_value_flash_mode').': '.l10n('exif_value_flash_mode_auto');break;
            case 3 << 3: $extraclass = " auto";break;
        }
        // 6th bit is red eye function
        if (($value & (1 << 6)) > 0)
        {
            $retValue .= ', '.l10n('exif_value_red_eye');
        }
        $metamore_settings .= '<div title="'.$metamore_tooltips['flash'].'"><span class="meta flash'.$extraclass.'"><img src="./plugins/metamore/images/placeholder.png"></span>'.ltrim($retValue, ", ").'</div>';
    }

    $template->assign('METAMORE_HEADLINE', $picture['current']['headline']);
    $template->assign('METAMORE_HARDWARE', $metamore_hardware);
    $template->assign('METAMORE_SETTINGS', $metamore_settings);
}

function metamore_prefilter($content)
{
    global $picture;

    $metamore_skeleton = '<div id="metamore">';
    $metamore_skeleton .= '<div id="hardware">{$METAMORE_HARDWARE}</div><!-- /hardware -->';
    $metamore_skeleton .= '<div id="settings">{$METAMORE_SETTINGS}</div><!-- /settings -->';
    $metamore_skeleton .= '</div><!-- /metamore -->{combine_css path="plugins/metamore/style.css"}';

    $search = '<p class="imageComment">';
    $replace = '<p class="headline">{$METAMORE_HEADLINE}</p><p class="imageComment">';

    $content = str_replace($search, $replace, $content);

    $search = '{$ELEMENT_CONTENT}';
    $replace = '{$ELEMENT_CONTENT}'.$metamore_skeleton;

    return str_replace($search, $replace, $content);
}
?>
