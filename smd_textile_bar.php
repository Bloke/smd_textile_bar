<?php

// This is a PLUGIN TEMPLATE for Textpattern CMS.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'smd_textile_bar';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.1.2';
$plugin['author'] = 'Stef Dawson / Julian Reisenberger / Jukka Svahn / Patrick Woods';
$plugin['author_uri'] = 'https://stefdawson.com/';
$plugin['description'] = 'Textile bar for the Write panel in Textpattern CMS';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public              : only on the public side of the website (default)
// 1 = public+admin        : on both the public and admin side
// 2 = library             : only when include_plugin() or require_plugin() is called
// 3 = admin               : only on the admin side (no AJAX)
// 4 = admin+ajax          : only on the admin side (AJAX supported)
// 5 = public+admin+ajax   : on both the public and admin side (AJAX supported)
$plugin['type'] = '4';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '3';

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String

$plugin['textpack'] = <<<EOT
#@language en, en-gb, en-us
#@prefs
smd_textile_bar => Textile bar
smd_textile_bar_acronym => Show abbreviation
smd_textile_bar_bc => Show block code (bc)
smd_textile_bar_body => Attach to Body field
smd_textile_bar_bq => Show block quote (bq)
smd_textile_bar_btn_acronym => Abbr
smd_textile_bar_btn_bc => Block code
smd_textile_bar_btn_bq => Block quote
smd_textile_bar_btn_code => Code
smd_textile_bar_btn_del => Delete
smd_textile_bar_btn_emphasis => Italics
smd_textile_bar_btn_form => Form
smd_textile_bar_btn_h1 => H1
smd_textile_bar_btn_h2 => H2
smd_textile_bar_btn_h3 => H3
smd_textile_bar_btn_h4 => H4
smd_textile_bar_btn_h5 => H5
smd_textile_bar_btn_h6 => H6
smd_textile_bar_btn_hx => Hx
smd_textile_bar_btn_image => Image
smd_textile_bar_btn_ins => Insert
smd_textile_bar_btn_link => Link
smd_textile_bar_btn_ol => Num list
smd_textile_bar_btn_output_form => Form
smd_textile_bar_btn_strong => Bold
smd_textile_bar_btn_sub => Subscript
smd_textile_bar_btn_sup => Superscript
smd_textile_bar_btn_ul => List
smd_textile_bar_buttons => Use buttons (text) / Show tooltips (icons)
smd_textile_bar_code => Show inline code
smd_textile_bar_del => Show delete
smd_textile_bar_emphasis => Show emphasis (italic)
smd_textile_bar_excerpt => Attach to Excerpt field
smd_textile_bar_features => Bar features
smd_textile_bar_form => Allow Form insertion from this group
smd_textile_bar_h1 => Show h1
smd_textile_bar_h2 => Show h2
smd_textile_bar_h3 => Show h3
smd_textile_bar_h4 => Show h4
smd_textile_bar_h5 => Show h5
smd_textile_bar_h6 => Show h6
smd_textile_bar_headings => Use individual headings
smd_textile_bar_icons => Use icons
smd_textile_bar_image => Show image
smd_textile_bar_ins => Show insert
smd_textile_bar_layout => Bar layout
smd_textile_bar_link => Show link
smd_textile_bar_strong => Show strong (bold)
smd_textile_bar_ol => Show ordered list (ol)
smd_textile_bar_output_form => Show form
smd_textile_bar_sub => Show subscript (sub)
smd_textile_bar_sup => Show superscript (sup)
smd_textile_bar_ul => Show unordered list (ul)
EOT;

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
/**
 * smd_textile_bar plugin for Textpattern CMS
 *
 * @author Stef Dawson / Julian Reisenberger / Jukka Svahn / Patrick Woods
 * @license GNU GPLv2
 * @link http://github.com/Bloke/smd_textile_bar
 */

if (txpinterface === 'admin') {
    $smd_textile_bar = new smd_textile_bar();
    $smd_textile_bar->install();
}

class smd_textile_bar
{
    protected $event = 'smd_textile_bar';
    protected $version = '0.1.2';
    protected $privs = '1,2';
    protected $all_privs = '1,2,3,4,5,6';

    /**
     * Constructor
     */
    public function __construct()
    {
        add_privs('plugin_prefs.'.$this->event, $this->privs);
        add_privs($this->event, $this->privs);
        add_privs('prefs.'.$this->event.'.'.$this->event.'_features', $this->privs);
        add_privs('prefs.'.$this->event.'.'.$this->event.'_layout', $this->all_privs);

        register_callback(array($this, 'prefs'), 'plugin_prefs.'.$this->event);
        register_callback(array($this, 'install'), 'plugin_lifecycle.'.$this->event);
        register_callback(array($this, 'head'), 'admin_side', 'head_end');

        $this->install();
    }

    /**
     * Installer.
     *
     * @param string $evt Admin-side event.
     * @param string $stp  Admin-side event, plugin-lifecycle step.
     */
    public function install($evt = '', $stp = '')
    {
        global $prefs;

        // Remove prefs if plugin deleted.
        // Also remove rah_textile_bar prefs, since the plugins can't co-exist.
        if ($stp == 'deleted') {
            safe_delete(
                'txp_prefs',
                "name like 'rah\_textile\_bar\_%' OR name like 'smd\_textile\_bar\_%'"
            );

            return;
        }

        // If the installed plugin is the current one, skip installation.
        $current = isset($prefs['smd_textile_bar_version']) ?
            $prefs['smd_textile_bar_version'] : 'base';

        if ($current == $this->version)
            return;

        // Install the prefs.
        $position = 230;

        $values['features'] = array_keys($this->buttons());
        $values['layout'] = array('body', 'excerpt', 'buttons', 'icons', 'headings');

        foreach ($values as $group => $set) {
            $scope = ($group === 'layout') ? PREF_PRIVATE : PREF_GLOBAL;

            foreach ($set as $n) {
                if ($n === 'form') {
                    $html = $this->event.'->getFormTypes';
                    $val = '';
                } else {
                    $html = 'yesnoradio';
                    $val = 1;
                }

                $name = 'smd_textile_bar_'.$n;

                if (!isset($prefs[$name])) {
                    set_pref($name, $val, 'smd_textile_bar.smd_textile_bar_'.$group, PREF_PLUGIN, $html, $position, $scope);
                    $prefs[$name] = 1;
                }

                $position++;
            }
        }

        // Remove obsolete prefs.
        safe_delete(
            'txp_prefs',
            "name LIKE 'rah\_textile\_bar\_h_' OR name='rah_textile_bar_codeline'"
        );

        // Update the installed version number.
        set_pref('smd_textile_bar_version', $this->version, $this->event, 2, '', 0);
        $prefs['smd_textile_bar_version'] = $this->version;
    }

    /**
     * Lists buttons
     *
     * @return array Array of buttons.
     */
    public function buttons()
    {
        return array(
            'strong' => array(
                'callback' => 'inline',
                'before'   => '*',
                'after'    => '*',
                ),
            'emphasis' => array(
                'callback' => 'inline',
                'before'   => '_',
                'after'    => '_',
                ),
            'ins' => array(
                'callback' => 'inline',
                'before'   => '+',
                'after'    => '+',
                ),
            'del' => array(
                'callback' => 'inline',
                'before'   => '-',
                'after'    => '-',
                ),
            'h1' => array(
                'callback' => 'heading',
                'level'    => '1',
                ),
            'h2' => array(
                'callback' => 'heading',
                'level'    => '2',
                ),
            'h3' => array(
                'callback' => 'heading',
                'level'    => '3',
                ),
            'h4' => array(
                'callback' => 'heading',
                'level'    => '4',
                ),
            'h5' => array(
                'callback' => 'heading',
                'level'    => '5',
                ),
            'h6' => array(
                'callback' => 'heading',
                'level'    => '6',
                ),
            'sup' => array(
                'callback' => 'inline',
                'before'   => '^',
                'after'    => '^',
                ),
            'sub' => array(
                'callback' => 'inline',
                'before'   => '~',
                'after'    => '~',
                ),
            'link' => array(
                'callback' => 'link',
                ),
            'ul' => array(
                'callback' => 'list',
                'bullet'   => '*',
                ),
            'ol' => array(
                'callback' => 'list',
                'bullet'   => '#',
                ),
            'image' => array(
                'callback' => 'inline',
                'before'   => '!',
                'after'    => '!',
                ),
            'code' => array(
                'callback' => 'inline',
                'before'   => '@',
                'after'    => '@',
                ),
            'bc' => array(
                'callback' => 'block',
                'tag'      => 'bc',
                ),
            'bq' => array(
                'callback' => 'block',
                'tag'      => 'bq',
                ),
            'acronym' => array(
                'callback' => 'acronym',
                ),
            'form' => array(
                'callback' => 'form',
                'before'   => '<txp::',
                'after'    => ' />',
            ),
        );
    }

    /**
     * Fetch form types as a select list
     *
     * @return string HTML
     */
    public function getFormTypes($key, $val)
    {
        $instance = Txp::get('Textpattern\Skin\Form');

        $form_types = array();

        foreach ($instance->getTypes() as $type) {
            $form_types[$type] = gTxt($type);
        }

        return selectInput('smd_textile_bar_form', $form_types, $val, true);
    }

    /**
     * Fetch forms of the given type
     *
     * @param string $type The form type (group)
     * @return array Form names
     */
    protected function getFormsOfType($type)
    {
        return safe_column('name', 'txp_form', "type='".doSlash($type)."'");
    }

    /**
     * All the required scripts and styles.
     */
    public function head()
    {
        global $event, $prefs;

        if ($event !== 'article')
            return;

        $fields = array('body', 'excerpt');
        $buttons = $this->buttons();

        foreach ($fields as $key => $field) {
            if (empty($prefs['smd_textile_bar_'.$field])) {
                unset($fields[$key]);
            }
        }

        if (!empty($prefs['smd_textile_bar_additional_fields'])) {
            $fields += do_list($prefs['smd_textile_bar_additional_fields']);
        }

        $separate_headings = get_pref('smd_textile_bar_headings');

        $js = '';
        $aclass = array();
        $use_buttons = get_pref('smd_textile_bar_buttons');
        $use_icons = get_pref('smd_textile_bar_icons');
        if ($use_buttons) {
            if ($use_icons) {
                $aclass[] = 'ui-controlgroup smd_textile_bar-tooltips';
            }
        } else {
            $aclass[] = 'ui-controlgroup';
        }
        $class_str = implode(' ', $aclass);

        foreach ($fields as $field) {
            $html = array();
            $html[] = '<div class="smd_textile_bar '.$field.' '.$class_str.'">';
            $used_headings = array();
            $headings_done = false;

            foreach ($buttons as $key => $opts) {
                if (!get_pref('smd_textile_bar_'.$key)) {
                    continue;
                }

                if (!$separate_headings && in_array($key, array('h1', 'h2', 'h3', 'h4', 'h5', 'h6'))) {
                    $used_headings[] = $key;
                    continue;
                }

                // Combine headings into a single button if necessary.
                if (!$separate_headings && !$headings_done && $used_headings) {
                    $head_opts = array(
                        'callback' => 'heading',
                        'level' => filter_var($used_headings[0], FILTER_SANITIZE_NUMBER_INT)
                    );

                    $html[] = $this->getButton($field, 'hx', $head_opts, compact('use_icons', 'use_buttons'));
                    $headings_done = true;
                }

                $html[] = $this->getButton($field, $key, $opts, compact('use_icons', 'use_buttons'));
            }

            $html[] = '</div>';
            $html_str = implode(n, $html);
            $js .=
                '$(document).ready(function(){'.
                    '$("textarea#'.escape_js($field).'").before("'.escape_js($html_str).'")'.
                '});';
        }

        // Drop the CSS, JavaScript and SVG icons on the page.
        $style = $this->getStyles();
        $icons = \Txp::get('\Textpattern\Plugin\Plugin')->fetchData($this->event);
        $js .= $this->getJS($used_headings);

        echo '<style>' . $style . '</style>';
        echo script_js($js);
        echo $icons;
    }

    /**
     * Return an fully formed button for the toolbar.
     *
     * @param  string $field  Where the button should appear (body / excerpt)
     * @param  string $key    Button designator
     * @param  array  $opts   Button options
     * @param  array  $extras Additional display info
     * @return string         HTML
     */
    protected function getButton($field, $key, $opts, $extras)
    {
        $params = array();

        foreach ($opts as $data => $val) {
            $params[] = 'data-'.$data.'="'.htmlentities($val).'"';
        }

        if ($extras['use_icons']) {
            $content = '<span class="ui-icon ui-icon-smd_textile_bar-'.$key.'">'.gTxt('smd_textile_bar_btn_'.$key).'</span>';
            $title = ' title="'.gTxt('smd_textile_bar_btn_'.$key).'"';
            $class= '';
        } else {
            $content = gTxt('smd_textile_bar_btn_'.$key);
            $title = '';
            if ($extras['use_buttons']) {
                $class= ' ui-corner-all';
            } else {
                $class= '';
            }
        }

        return '<a role="button" class="ui-button'.$class.' smd_textile_bar_btn"'.$title.' href="#'.$field.'" '.implode(' ', $params).'>'.$content.'</a>';
    }

    /**
     * Redirects to the preferences panel
     */
    public function prefs()
    {
        header('Location: ?event=prefs#prefs_group_smd_textile_bar');
        echo
            '<p id="message">'.n.
            '   <a href="?event=prefs#prefs_group_smd_textile_bar">'.gTxt('continue').'</a>'.n.
            '</p>';
    }

    /**
     * Return the CSS used in the plugin.
     *
     * @return string CSS
     */
    protected function getStyles()
    {
        $styles = <<<EOCSS
.smd_textile_bar .ui-icon {
    width: 1.538461538461538em;
    height: 1.538461538461538em;
    background-size: 1.538461538461538em 1.538461538461538em;
}
.smd_textile_bar .ui-corner-all {
    margin-bottom: 0.3em;
}

/* Tooltip styles */
.smd_textile_bar-tooltips .smd_textile_bar_btn {
  position: relative;
}
.smd_textile_bar-tooltips .smd_textile_bar_btn::before,
.smd_textile_bar-tooltips .smd_textile_bar_btn::after {
  text-transform: none;
  font-size: .9em;
  line-height: 1;
  user-select: none;
  pointer-events: none;
  position: absolute;
  display: none;
  opacity: 0;
  left: 50%;
  transform: translate(-50%, -.25em);
}
.smd_textile_bar-tooltips .smd_textile_bar_btn::before {
  content: '';
  border: 5px solid transparent;
  z-index: 1001;
  bottom: 100%;
  border-bottom-width: 0;
  border-top-color: #333;
}
.smd_textile_bar-tooltips .smd_textile_bar_btn::after {
  content: attr(title);
  text-align: center;
  min-width: 3em;
  max-width: 21em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  padding: 1ch 1.5ch;
  border-radius: .5ch;
  box-shadow: 0 1em 2em -.5em rgba(0, 0, 0, 0.35);
  background: #333;
  color: #fff;
  z-index: 1000;
  bottom: calc(100% + 5px);
}
.smd_textile_bar-tooltips .smd_textile_bar_btn:hover::before,
.smd_textile_bar-tooltips .smd_textile_bar_btn:hover::after {
  display: block;
  opacity: 1;
}

/* don't show empty tooltips */
.smd_textile_bar-tooltips .smd_textile_bar_btn[title='']::before,
.smd_textile_bar-tooltips .smd_textile_bar_btn[title='']::after {
  display: none !important;
}

/* Icon set */
.ui-icon-smd_textile_bar-strong { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='m12 11.667h-4m4 0s3.333 0 3.333-3.333-3.333-3.333-3.333-3.333h-3.4c-.331 0-.6.269-.6.6v6.067m4-.001s4 0 4 3.667-4 3.667-4 3.667h-3.4c-.331 0-.6-.269-.6-.6v-6.733' fill='none' stroke='%23333' stroke-width='2'/%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-emphasis { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='m11 5h3m3 0h-3m0 0-4 14m0 0h-3m3 0h3' fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'/%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-underline { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m16 5v6c0 2.209-1.791 4-4 4-2.209 0-4-1.791-4-4v-6'/%3E%3Cpath d='m6 19h12'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-strikethrough { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m16.3 6.8c-1.1-1.1-3-1.8-4.7-1.9-2.1 0-3.9.9-3.9 3.5 0 4.7 8.6 2.4 8.6 7.1 0 2.7-2.3 3.8-4.7 3.8-1.8-.1-3.7-.8-4.7-2.2'/%3E%3Cpath d='m4.5 11.7h14.2'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-heading { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m6.8 5v14'/%3E%3Cpath d='m17.2 5v14'/%3E%3Cpath d='m6.8 12h10.4'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-h1 { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m21.5 12v7'/%3E%3Cpath d='m21.5 12-2.5 1.5'/%3E%3Cpath d='m13.7 5v14'/%3E%3Cpath d='m4.3 5v14'/%3E%3Cpath d='m4.3 12h9.4'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-h2 { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m22.4 19h-4.2l2.9-3.5s1.8-1.9.1-3.1c-1.1-.8-2.2-.4-2.9.6'/%3E%3Cpath d='m13.7 5v14'/%3E%3Cpath d='m4.3 5v14'/%3E%3Cpath d='m4.3 12h9.4'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-h3 { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m18.2 12h3.6c-.7.9-1.3 1.9-2 2.8h.2c1.4 0 2.4.9 2.4 2.1s-1.1 2.1-2.4 2.1c-.2 0-.6 0-1-.2-.3-.1-.7-.3-.8-.5'/%3E%3Cpath d='m13.7 5v14'/%3E%3Cpath d='m4.3 5v14'/%3E%3Cpath d='m4.3 12h9.4'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-h4 { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m21.8 12-3.5 5.2h4.1v1.8'/%3E%3Cpath d='m13.7 5v14'/%3E%3Cpath d='m4.3 5v14'/%3E%3Cpath d='m4.3 12h9.4'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-h5 { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m13.7 5v14'/%3E%3Cpath d='m4.3 5v14'/%3E%3Cpath d='m4.3 12h9.4'/%3E%3Cpath d='m21.8 12c-1.2 0-2.5 0-3.6 0v3.3c.3 0 .9-.2 1.6-.2h.1c1.5-.1 2.5.7 2.5 1.9 0 1.3-1.1 2.1-2.5 2.1-.2 0-.6 0-1-.2-.4-.1-.7-.4-.8-.5'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-h6 { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m22.4 16.9c0 1.16-.985 2.1-2.2 2.1s-2.2-.94-2.2-2.1.985-2.1 2.2-2.1 2.2.94 2.2 2.1z'/%3E%3Cpath d='m20.3 12-2.1 3.8'/%3E%3Cpath d='m13.7 5v14'/%3E%3Cpath d='m4.3 5v14'/%3E%3Cpath d='m4.3 12h9.4'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-hx { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m13.7 5v14'/%3E%3Cpath d='m4.3 5v14'/%3E%3Cpath d='m4.3 12h9.4'/%3E%3Cpath d='m17.4 19 2.5-2.5m2.5-2.5-2.5 2.5m0 0-2.5-2.5m2.5 2.5 2.5 2.5'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-sup { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m6.5 9.8 8.2 8.2'/%3E%3Cpath d='m14.7 9.8-8.2 8.2'/%3E%3Cpath d='m22.4 13.4h-4.1l2.9-3.5s1.7-1.8.1-3c-1-.8-2.2-.3-2.9.6'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-sub { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m6.5 9.8 8.2 8.2'/%3E%3Cpath d='m14.7 9.8-8.2 8.2'/%3E%3Cpath d='m22.4 20.8h-4.1l2.9-3.5s1.7-1.8.1-3c-1-.8-2.2-.3-2.9.6'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-ul { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round'%3E%3Cg stroke-width='4'%3E%3Cpath d='M4 6v0'%3E%3C/path%3E%3Cpath d='M4 15.3v0'%3E%3C/path%3E%3C/g%3E%3Cg stroke-width='2'%3E%3Cpath d='M10.8 6h9.2'%3E%3C/path%3E%3Cpath d='M10.8 15.3h9.2'%3E%3C/path%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-ol { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m10.8 6h9.2'/%3E%3Cpath d='m2.9 4.6 1.9-1.2v5.5'/%3E%3Cpath d='m6.1 18h-3.2l2.2-2.7s1.4-1.5.1-2.4c-.8-.6-1.7-.3-2.2.5'/%3E%3Cpath d='m10.8 15.3h9.2'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-acronym { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m22.5 15.5h-3.3'/%3E%3Cpath d='m22.5 8.5h-3.3'/%3E%3Cpath d='m20.8 15.5v-7'/%3E%3Cpath d='m15.8 8.6-3.2 3.9-3.2-4'/%3E%3Cpath d='m12.6 12.5v3'/%3E%3Cpath d='m1.5 8.5v7'/%3E%3Cpath d='m1.5 12h3.3'/%3E%3Cpath d='m1.5 8.5h4.5'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-ins { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m21.5 8.5h-2.2c-1 0-1.8.8-1.8 1.8s.8 1.8 1.8 1.8h1.5c1 0 1.8.8 1.8 1.8s-.8 1.8-1.8 1.8h-3.2'/%3E%3Cpath d='m4.8 15.5h-3.3'/%3E%3Cpath d='m4.8 8.5h-3.3'/%3E%3Cpath d='m3.1 15.5v-7'/%3E%3Cpath d='m8.5 15.5v-7l5.4 7v-7'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-del { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m9.8 8.5v7'/%3E%3Cpath d='m9.8 12h3.3'/%3E%3Cpath d='m9.8 15.5h4.5'/%3E%3Cpath d='m9.8 8.5h4.5'/%3E%3Cpath d='m17.5 15.5v-7'/%3E%3Cpath d='m22.3 15.5c-1.9 0-4 0-4.8 0'/%3E%3Cpath d='m1.5 12v-3.5c2.5 0 5 0 5 3.5s-2.5 3.5-5 3.5z'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-link { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m14 11.998c0-2.492-2.317-4.998-5.143-4.998-.335 0-1.438 0-1.714 0-2.84 0-5.143 2.238-5.143 4.998 0 2.378 1.71 4.369 4 4.874.368.081.75.124 1.143.124'/%3E%3Cpath d='m10 11.998c0 2.492 2.317 4.998 5.143 4.998h1.714c2.84 0 5.143-2.238 5.143-4.998 0-2.378-1.71-4.369-4-4.874-.368-.081-.75-.124-1.143-.124'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-code { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m13.5 6-3.5 12.5'/%3E%3Cpath d='m6.5 8.5-3.5 3.5 3.5 3.5'/%3E%3Cpath d='m17.5 8.5 3.5 3.5-3.5 3.5'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-bc { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m13 7.3-2.3 8.5'/%3E%3Cpath d='m8.2 9-2.3 2.3 2.3 2.3'/%3E%3Cpath d='m15.8 9 2.3 2.3-2.3 2.3'/%3E%3Cpath d='m2 17.9v-12.8c0-1.1.9-2.1 2-2.1h16c1.1 0 2 1 2 2.1v12.7c0 1.2-.9 2.1-2 2.1h-16c-1.1.1-2-.9-2-2z'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-bq { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-width='2'%3E%3Cpath d='m3 20.3v-15.3c0-1.1.9-2 2-2h14c1.1 0 2 .9 2 2v10c0 1.1-.9 2-2 2h-11c-.6 0-1.2.3-1.6.8l-2.3 2.9c-.4.4-1.1.2-1.1-.4z'/%3E%3Cg stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m16.5 10.5c.3 0 .5-.2.5-.5s-.2-.5-.5-.5-.5.2-.5.5.2.5.5.5z'/%3E%3Cpath d='m12 10.5c.3 0 .5-.2.5-.5s-.2-.5-.5-.5-.5.2-.5.5.2.5.5.5z'/%3E%3Cpath d='m7.5 10.5c.3 0 .5-.2.5-.5s-.2-.5-.5-.5-.5.2-.5.5.2.5.5.5z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-image { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m5 3h14c1.105 0 2 .895 2 2v14c0 1.105-.895 2-2 2h-14c-1.105 0-2-.895-2-2v-14c0-1.105.895-2 2-2z'/%3E%3Cpath d='m10 8.5c0 .828-.672 1.5-1.5 1.5s-1.5-.672-1.5-1.5.672-1.5 1.5-1.5 1.5.672 1.5 1.5z'/%3E%3Cpath d='m21 15-5-5-11 11'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-file { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m9 12h3m3 0h-3m0 0v-3m0 3v3'/%3E%3Cpath d='m4 21.4v-18.8c0-.331.269-.6.6-.6h11.652c.159 0 .312.063.424.176l3.149 3.149c.113.113.176.265.176.424v15.651c0 .331-.269.6-.6.6h-14.8c-.331 0-.6-.269-.6-.6z'/%3E%3Cpath d='m16 5.4v-3.046c0-.195.158-.354.354-.354.094 0 .184.037.25.104l3.293 3.293c.066.066.104.156.104.25 0 .195-.158.354-.354.354h-3.046c-.331 0-.6-.269-.6-.6z'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-form { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m9 12h3m3 0h-3m0 0v-3m0 3v3'/%3E%3Cpath d='m11.7 1.173c.186-.107.414-.107.6 0l8.926 5.154c.186.107.3.305.3.52v10.307c0 .214-.114.412-.3.52l-8.926 5.154c-.186.107-.414.107-.6 0l-8.926-5.154c-.186-.107-.3-.305-.3-.52v-10.307c0-.214.114-.412.3-.52z'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-short-tag-circle { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m8 12h4m4 0h-4m0 0v-4m0 4v4'/%3E%3Cpath d='m12 22c5.523 0 10-4.477 10-10s-4.477-10-10-10-10 4.477-10 10 4.477 10 10 10z'/%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-strong_de { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-width='2'%3E%3Cpath d='m13.9 11.7h-5.9' stroke-linecap='round' stroke-linejoin='round'/%3E%3Cpath d='m12.4 5h-3.8c-.3 0-.6.3-.6.6v6.1'/%3E%3Cg stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m8 19v-7.3'/%3E%3Cpath d='m15.4 5h-3'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); }
.ui-icon-smd_textile_bar-emphasis_de { background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'%3E%3Cpath d='m9.9 5-2.9 14'/%3E%3Cpath d='m16.1 19-5.2-7.4'/%3E%3Cpath d='m18.2 5-10.1 9'/%3E%3C/g%3E%3C/svg%3E"); }
EOCSS;

        return $styles;
    }

    /**
     * Return the JavaScript used in the plugin.
     *
     * @param array $headings Set of heading tags in use
     * @return string CSS
     */
    protected function getJS($headings)
    {
        $formlist = json_encode(array_keys($this->getFormsOfType(get_pref('smd_textile_bar_form'))));
        $head_levels = json_encode($headings);

        $js = <<<EOJS
(function($, len, createRange, duplicate){

    var opt = {}, is = {}, form = {$formlist}, words = {}, lines = {};

    var methods = {

        /**
         * Initialize
         */

        init : function() {
            this.click(function(e) {
                e.preventDefault();

                $.each(this.attributes, function(index, attr) {
                    if (attr.name.indexOf('data-') === 0) {
                        opt[attr.name.substr(5)] = attr.value;
                    }
                });

                opt.field = $($(this).attr('href'));
                opt.field.focus();
                opt.selection = methods.caret.apply(opt.field);

                words = { start : 0, end : 0, text : [] };
                lines = { start : 0, end : 0, text : [] };

                var i = 0, ls = 0, le = 0;

                $.each(opt.field.val().split(/\\r\\n|\\r|\\n/), function(index, line) {

                    if (ls > opt.selection.end) {
                        return;
                    }

                    le = ls+line.length;

                    if (le >= opt.selection.start) {

                        if (!lines.text[0]) {
                            lines.start = ls;
                        }

                        lines.text.push(line);
                        lines.end = le;
                    }

                    ls = le+1;

                    $.each(line.split(' '), function(index, w) {

                        if (i > opt.selection.end) {
                            return;
                        }

                        if (i+w.length >= opt.selection.start) {

                            if (!words.text[0]) {
                                words.start = i;
                            }

                            words.text.push(w);
                            words.end = i+w.length;
                        }

                        i += w.length+1;
                    });
                });

                opt.selection.char_before = (
                    opt.selection.start < 1 ?
                        '' : opt.field.val().substr(opt.selection.start-1, 1)
                );

                is.empty = (!opt.selection.text);
                is.whitespace = (!is.empty && !$.trim(opt.selection.text));
                is.inline = (opt.selection.text.indexOf("\\n") == -1);

                is.linefirst = (
                    opt.selection.start < 1 ||
                    opt.selection.char_before == "\\n" ||
                    opt.selection.char_before == "\\r"
                );

                var offset = lines.end;
                var c = opt.field.val();

                is.paragraph = (
                    c.indexOf("\\n\\n", offset) >= 0 ||
                    c.indexOf("\\r\\n\\r\\n", offset) >= 0
                );

                is.block = (
                    !is.paragraph &&
                    c.indexOf("\\n", offset) >= 0 ||
                    c.indexOf("\\r\\n", offset) >= 0
                );

                if (!format[opt.callback]) {
                    return;
                }

                var f = format[opt.callback]();

                if (f) {
                    opt.field.val(f);
                }

                methods.caret.apply(opt.field, [{
                    start : opt.selection.end,
                    end : opt.selection.end
                }]);
            });
        },

        /**
         * Caret code based on jCaret
         * @author C. F., Wong (Cloudgen)
         * @link http://code.google.com/p/jcaret/
         *
         * Copyright (c) 2010 C. F., Wong (http://cloudgen.w0ng.hk)
         * Licensed under the MIT License:
         * http://www.opensource.org/licenses/mit-license.php
         */

        caret : function(options) {
            var start, end, t = this[0];

            if (
                typeof options === "object" &&
                typeof options.start === "number" &&
                typeof options.end === "number"
            ) {
                start = options.start;
                end = options.end;
            }

            if (typeof start != "undefined") {
                this[0].selectionStart = start;
                this[0].selectionEnd = end;
                this[0].focus();
                return this;
            } else {
                var s = t.selectionStart,
                e = t.selectionEnd;

                return {
                    start : s,
                    end : e,
                    text : t.value.substring(s,e)
                };
            }
        }
    };

    /**
     * Replaces selection with Textile markup
     * @param string string
     * @param int start
     * @param int end
     */

    var insert = function(string, start, end) {
        if (typeof start === "undefined") {
            start = opt.selection.start;
        }

        if (typeof end === "undefined") {
            end = opt.selection.end;
        }

        opt.field.val(opt.field.val().substring(0, start) + string + opt.field.val().substring(end));
        opt.selection.end = start + string.length;
    };

    /**
     * Formatting methods
     */

    var format = {

        /**
         * Formats a code block
         */

        code : function() {
            if (
                (is.linefirst && is.empty) ||
                !is.inline
            ) {
                insert(
                    'bc. ' + $.trim(lines.text.join("\\n")),
                    lines.start,
                    lines.end
                );
                return;
            }

            format.inline();
        },

        /**
         * Formats lists: ul, ol
         */

        list : function() {
            var out = [];

            $.each(lines.text, function(key, line){
                out.push(( (is.linefirst && is.empty) || $.trim(line) ? opt.bullet + ' ' : '') + line);
            });

            out = out.join("\\n");

            insert(
                out,
                lines.start,
                lines.end
            );

            opt.selection.end = lines.start + out.length;
        },

        /**
         * Formats simple inline tags: strong, bold, em, ins, del
         */

        inline : function() {
            if (
                is.empty &&
                words.text.length == 1
            ) {
                opt.selection.start = words.start;
                opt.selection.end = words.end;
                opt.selection.text = words.text.join(' ');
            }

            var r = !is.whitespace && is.inline ?
                opt.before + opt.selection.text + opt.after :
                opt.selection.text + opt.before + opt.after;

            insert(r);
        },

        /**
         * Formats headings
         */

        heading : function() {
            var line = lines.text.join("\\n");
            var s = line.substr(0,3);
            var head_levels = {$head_levels};

            if (jQuery.inArray(s, ['h1.', 'h2.', 'h3.', 'h4.', 'h5.', 'h6.']) >= 0) {
                if (head_levels.length > 0 && (pos = jQuery.inArray(s.substr(0,2), head_levels)) > -1) {
                    var pos = (pos === head_levels.length - 1) ? 0 : pos + 1;
                    s = parseInt(head_levels[pos].substr(1,1));
                } else {
                    s = opt.level;
                }

                insert(s, lines.start+1, lines.start+2);
                opt.selection.end = lines.start+line.length;
                return;
            }

            insert(
                'h' + opt.level +'. ' + line + (!is.paragraph ? "\\n\\n" : ''),
                lines.start,
                lines.end
            );
        },

        /**
         * Inserts form
         */

        form : function() {
            var line = lines.text.join("\\n");
            const regex = /^<txp::([A-Za-z0-9_.\-]+)/gu;
            var parts = regex.exec(line);

            if (parts !== null) {
                var capture = parts[1];

                if ((pos = form.indexOf(capture)) > -1) {
                    pos = (pos === (form.length)-1) ? 0 : pos+1;
                    insert(opt.before + form[pos], lines.start, lines.start+parts[0].length);
                    opt.selection.end = lines.start+parts[0].length;
                    return;
                } else {
                    var toAdd = opt.before + form[0] + opt.after;
                    insert(
                        (!is.paragraph ? "\\n\\n" : '') + toAdd,
                        lines.end + 2, lines.end + 2 + toAdd.length
                    );
                    return;
                }
            }

            insert(
                opt.before + form[0] + opt.after + line,
                lines.start,
                lines.end
            );
        },

        /**
         * Formats normal blocks
         */

        block : function() {
            insert(
                opt['tag'] +'. ' + $.trim(lines.text.join("\\n")) +
                (!is.paragraph ? "\\n\\n" : ''),
                lines.start,
                lines.end
            );
        },

        /**
         * Formats an image
         */

        image : function() {
        },

        /**
         * Formats a link
         */

        link : function() {
            var text = opt.selection.text;
            var link = 'https://';

            if (
                is.empty &&
                words.text.length == 1
            ) {
                opt.selection.start = words.start;
                opt.selection.end = words.end;
                text = words.text.join(' ');
            }

            if (text.indexOf('http://') == 0 || text.indexOf('https://') == 0) {
                link = text;
                text = '$';
            } else if (text.indexOf('www.') == 0) {
                link = 'https://'+text;
                text = '$';
            }

            insert('"' + text + '":'+link);
        },

        /**
         * Formats acronym
         */

        acronym : function() {
            var text = opt.selection.text;
            var abc = 'ABC';

            if (!is.empty) {
                if (words.text.length >= 3) {
                    matches = text.match(/\b(\w)/g);
                    abc = matches.join('').toUpperCase();
                } else if (/[:lower:]/.test(words.text[0]) === false) {
                    abc = text;
                    text = '';
                }

                opt.selection.start = words.start;
                opt.selection.end = words.end;
            }

            insert(abc+'('+text+')');
        }
    };

    $.fn.smd_textile_bar = function(method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method){
            return methods.init.apply(this, arguments);
        } else {
            $.error('[smd_textile_bar: unknown method '+method+']');
        }
    };

})(jQuery, 'length', 'createRange', 'duplicate');

/**
 * Hide the bar if user is not using Textile.
 */
function smd_textile_bar_toggle(ev) {
    var tf_name = ev.target.name;
    var tf_value = ev.target.value;
    var selpart = (tf_name === 'textile_body') ? 'body' : 'excerpt';
    var sel = $('.smd_textile_bar.'+selpart);

    if (tf_value == '1') {
        sel.show();
    } else {
        sel.hide();
    }
}

$(document).ready(function(){
    $("a.smd_textile_bar_btn").smd_textile_bar();
    $('.txp-textfilter-options .textfilter-value').on('change', smd_textile_bar_toggle).change();

});

EOJS;
        return $js;
    }
}

# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---
h1. smd_textile_bar

p. Brings a simple and minimal Quicktags Textile inserting bar to the backend. It's not a heavy and buggy WYSIWYG editor that screws your own HTML, but instead it's a simple bar that inserts Textile where you want. The plugin is forked from "rah_textile_bar by Gocom":https://forum.textpattern.com/viewtopic.php?id=28283.

h2. List of features

* A simple Textile insertion bar, offering the most common formatting options Textile has.
* Easy to use and install: just run the automated plugin installation and activate.
* Options can be configured via Textpattern's Preferences panel (Admin > Preferences > Textile Bar).

h2. Requirements

* Textpattern 4.7.3+

h2. Installation and usage

p. Download and copy the plugin code to the plugin installer textarea. Install and verify to begin the automatic setup. After activating the plugin, you will see the textile bar above Body and Excerpt textareas in the Write panel.

h2. Configuration

Visit the Preferences panel form Textpattern's back-end: Admin > Preferences > Textile Bar.

h3. Admin preferences

Users with Managing Editor or higher privileges can set which buttons are available on the toolbar. Set the radio buttons to Yes for whichever features you wish to display on the toolbar.

The _Allow Form insertion from this group_ option is special. Select a form type from the list and save the preferences to permit insertion of any form contained within the given group. This is very handy for custom shortcodes, as you can create a dedicated form group to house them and offer users the ability to insert them from the toolbar. Each click of the Form button on the toolbar cycles through the available forms. Leave this preference blank to prevent form insertion.

h3. Layout preferences

All users may customise how the bar looks. The following options are available:

* *Attach to Body field* makes the bar available above the Body textarea, when Use Textile is on.
* *Attach to Excerpt field* makes the bar available above the Excerpt textarea, when Use Textile is on.
* *Use buttons (text) / Show tooltips (icons)* will present the bar as a series of buttons instead of a single strip. If you elect to display icons instead of text (see next option), this setting governs whether tooltips are displayed.
* *Use icons* displays icons instead of text labels on the toolbar.
* *Use individual headings* will show discrete buttons for each permitted heading level. e.g. H2, H3, H4 buttons. If you set this to No then headings can be inserted via a single Hx button. Each click of the button will cycle through the permitted heading levels set by the administrator.

h2. Known issues

* Under Txp 4.7.x, the bar doesn't disappear when the textfilter options are toggled.

# --- END PLUGIN HELP ---
-->
<?php
}
?>