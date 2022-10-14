<?php
/**
 * This file is part of Oveleon Contao Cookiebar.
 *
 * @package     contao-cookiebar
 * @license     AGPL-3.0
 * @author      Daniele Sciannimanica <https://github.com/doishub>
 * @copyright   Oveleon <https://www.oveleon.de/>
 */

use Contao\DC_Table;
use Contao\DataContainer;

$GLOBALS['TL_DCA']['tl_cookie_group'] = array
(
    // Config
    'config' => array
    (
        'dataContainer'               => DC_Table::class,
        'ptable'                      => 'tl_cookiebar',
        'ctable'                      => array('tl_cookie'),
        'switchToEdit'                => true,
        'enableVersioning'            => true,
        'markAsCopy'                  => 'title',
        'onload_callback' => array
        (
            array('tl_cookie_group', 'checkPermission')
        ),
        'sql' => array
        (
            'keys' => array
            (
                'id' => 'primary',
                'pid,published' => 'index'
            )
        )
    ),

    // List
    'list' => array
    (
        'sorting' => array
        (
            'mode'                    => DataContainer::SORT_INITIAL_LETTERS_DESC,
            'fields'                  => array('sorting'),
            'headerFields'            => array('title'),
            'panelLayout'             => 'limit',
            'child_record_callback'   => array('tl_cookie_group', 'listCookieGroup'),
            'child_record_class'      => 'no_padding'
        ),
        'label' => array
        (
            'fields'                  => array('title'),
            'format'                  => '%s'
        ),
        'global_operations' => array
        (
            'all' => array
            (
                'href'                => 'act=select',
                'class'               => 'header_edit_all',
                'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            )
        ),
        'operations' => array
        (
            'edit' => array
            (
                'href'                => 'table=tl_cookie',
                'icon'                => 'edit.svg'
            ),
            'editheader' => array
            (
                'href'                => 'act=edit',
                'icon'                => 'header.svg'
            ),
            'copy' => array
            (
                'href'                => 'act=paste&amp;mode=copy',
                'icon'                => 'copy.svg',
                'button_callback'     => array('tl_cookie_group', 'disableAction')
            ),
            'cut' => array
            (
                'href'                => 'act=paste&amp;mode=cut',
                'icon'                => 'cut.svg',
                'attributes'          => 'onclick="Backend.getScrollOffset()"',
                'button_callback'     => array('tl_cookie_group', 'disableAction')
            ),
            'delete' => array
            (
                'href'                => 'act=delete',
                'icon'                => 'delete.svg',
                'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
                'button_callback'     => array('tl_cookie_group', 'disableAction')
            ),
            'toggle' => array
            (
                'href'                => 'act=toggle&amp;field=published',
                'icon'                => 'visible.svg',
                'showInHeader'        => true
            ),
            'show' => array
            (
                'href'                => 'act=show',
                'icon'                => 'show.svg'
            )
        )
    ),

    // Palettes
    'palettes' => array
    (
        'default'                     => '{title_legend},title,published;description'
    ),

    // Fields
    'fields' => array
    (
        'id' => array
        (
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),
        'pid' => array
        (
            'foreignKey'              => 'tl_cookiebar.title',
            'sql'                     => "int(10) unsigned NOT NULL default 0",
            'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
        ),
        'sorting' => array
        (
            'sql'                     => "int(10) unsigned NOT NULL default 0"
        ),
        'identifier' => array
        (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'tstamp' => array
        (
            'sql'                     => "int(10) unsigned NOT NULL default 0"
        ),
        'title' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_cookie_group']['title'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'description' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_cookie_group']['description'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'textarea',
            'eval'                    => array('rte'=>'tinyMCE', 'helpwizard'=>true),
            'explanation'             => 'insertTags',
            'sql'                     => "mediumtext NULL"
        ),
        'published' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_cookie_group']['published'],
            'exclude'                 => true,
            'filter'                  => true,
            'toggle'                  => true,
            'inputType'               => 'checkbox',
            'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
        )
    )
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 */
class tl_cookie_group extends Contao\Backend
{
    /**
     * Import the back end user object
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('Contao\BackendUser', 'User');
    }

    /**
     * Check permissions to edit table tl_cookie_group
     */
    public function checkPermission()
    {
        $strAct = Contao\Input::get('act');

        if($strAct == 'deleteAll' || $strAct == 'copyAll' || $strAct == 'cutAll')
        {
            /** @var Symfony\Component\HttpFoundation\Session\SessionInterface $objSession */
            $objSession = Contao\System::getContainer()->get('session');
            $session = $objSession->all();

            if($strAct == 'deleteAll')
            {
                $currentIds = $session['CURRENT']['IDS'];
            }
            else
            {
                $currentIds = $session['CLIPBOARD']['tl_cookie_group']['id'];
            }

            // Set allowed cookie group IDs (delete multiple)
            if (is_array($currentIds))
            {
                $arrIds = array();

                foreach ($currentIds as $id)
                {
                    $objGroup = $this->Database->prepare("SELECT id, pid, identifier FROM tl_cookie_group WHERE id=?")
                        ->limit(1)
                        ->execute($id);

                    if ($objGroup->numRows < 1)
                    {
                        continue;
                    }

                    // Locked groups cannot be deleted
                    if ($objGroup->identifier !== 'lock')
                    {
                        $arrIds[] = $id;
                    }
                }

                if($strAct == 'deleteAll')
                {
                    $session['CURRENT']['IDS'] = $arrIds;
                }
                else
                {
                    if(empty($arrIds))
                    {
                        $session['CLIPBOARD']['tl_cookie_group'] = $arrIds;
                    }
                    else
                    {
                        $session['CLIPBOARD']['tl_cookie_group']['id'] = $arrIds;
                    }
                }
            }

            // Overwrite session
            $objSession->replace($session);
        }
    }

    /**
     * List a group item
     *
     * @param array $arrRow
     *
     * @return string
     */
    public function listCookieGroup($arrRow)
    {
        return '<div class="tl_content_left">' . $arrRow['title'] . '</div>';
    }

    /**
     * Return the delete cookie group button
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function disableAction($row, $href, $label, $title, $icon, $attributes)
    {
        // Disable the button if the element is locked
        if ($row['identifier'] === 'lock')
        {
            return Contao\Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
        }

        return '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . Contao\StringUtil::specialchars($title) . '"' . $attributes . '>' . Contao\Image::getHtml($icon, $label) . '</a> ';
    }
}
