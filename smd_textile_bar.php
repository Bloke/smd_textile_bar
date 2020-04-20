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

$plugin['version'] = '0.1.0';
$plugin['author'] = 'Stef Dawson / Jukka Svahn / Patrick Woods';
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
smd_textile_bar_btn_h1 => H1
smd_textile_bar_btn_h2 => H2
smd_textile_bar_btn_h3 => H3
smd_textile_bar_btn_h4 => H4
smd_textile_bar_btn_h5 => H5
smd_textile_bar_btn_h6 => H6
smd_textile_bar_btn_image => Image
smd_textile_bar_btn_ins => Insert
smd_textile_bar_btn_link => Link
smd_textile_bar_btn_ol => Num list
smd_textile_bar_btn_output_form => Form
smd_textile_bar_btn_strong => Bold
smd_textile_bar_btn_sub => Subscript
smd_textile_bar_btn_sup => Superscript
smd_textile_bar_btn_ul => List
smd_textile_bar_buttons => Use buttons
smd_textile_bar_code => Show inline code
smd_textile_bar_del => Show delete
smd_textile_bar_emphasis => Show emphasis (italic)
smd_textile_bar_excerpt => Attach to Excerpt field
smd_textile_bar_h1 => Show h1
smd_textile_bar_h2 => Show h2
smd_textile_bar_h3 => Show h3
smd_textile_bar_h4 => Show h4
smd_textile_bar_h5 => Show h5
smd_textile_bar_h6 => Show h6
smd_textile_bar_icons => Use icons
smd_textile_bar_image => Show image
smd_textile_bar_ins => Show insert
smd_textile_bar_link => Show link
smd_textile_bar_strong => Show strong (bold)
smd_textile_bar_ol => Show ordered list (ol)
smd_textile_bar_output_form => Show form
smd_textile_bar_sub => Show subscript (sub)
smd_textile_bar_sup => Show supersrcipt (sup)
smd_textile_bar_ul => Show unordered list (ul)
EOT;

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
/**
 * smd_textile_bar plugin for Textpattern CMS
 *
 * @author Stef Dawson / Jukka Svahn / Patrick Woods
 * @license GNU GPLv2
 * @link http://github.com/Bloke/smd_textile_bar
 */

if (txpinterface === 'admin') {
    $smd_textile_bar = new smd_textile_bar();
    $smd_textile_bar->install();
}

class smd_textile_bar
{
    protected $version = '0.1.0';
    protected $privs = '1,2';

    /**
     * Constructor
     */
    public function __construct()
    {
        add_privs('plugin_prefs.smd_textile_bar', $this->privs);
        add_privs('smd_textile_bar', $this->privs);
        add_privs('prefs.smd_textile_bar', $this->privs);

        register_callback(array($this, 'prefs'), 'plugin_prefs.smd_textile_bar');
        register_callback(array($this, 'install'), 'plugin_lifecycle.smd_textile_bar');
        register_callback(array($this, 'head'), 'admin_side', 'head_end');

        $this->install();
    }

    /**
     * Installer
     *
     * @param string $event Admin-side event.
     * @param string $step  Admin-side event, plugin-lifecycle step.
     */
    public function install($event = '', $step = '')
    {
        global $prefs;

        if ($step == 'deleted') {
            safe_delete(
                'txp_prefs',
                "name like 'rah\_textile\_bar\_%' OR name like 'smd\_textile\_bar\_%'"
            );

            return;
        }

        $current = isset($prefs['smd_textile_bar_version']) ?
            $prefs['smd_textile_bar_version'] : 'base';

        if ($current == $this->version)
            return;

        $position = 230;

        $values = array_keys($this->buttons());
        $values[] = 'excerpt';
        $values[] = 'body';
        $values[] = 'buttons';
        $values[] = 'icons';

        foreach ($values as $n) {
            $name = 'smd_textile_bar_'.$n;

            if (!isset($prefs[$name])) {
                safe_insert(
                    'txp_prefs',
                    "name='".doSlash($name)."',
                    val='1',
                    type=1,
                    event='smd_textile_bar',
                    html='yesnoradio',
                    position=".$position
                );

                $prefs[$name] = 1;
            }

            $position++;
        }

        safe_delete(
            'txp_prefs',
            "name LIKE 'rah\_textile\_bar\_h_' OR name='smd_textile_bar_codeline'"
        );

        set_pref('smd_textile_bar_version', $this->version,'smd_textile_bar', 2, '', 0);
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
        );
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

        $js = '';
        $style = <<<EOCSS
.smd_textile_bar {
    margin: 0 0 0.2em;
    display: flex;
    flex-wrap: wrap;
}
.smd_textile_bar--buttons a {
    padding: 0.25em 0.5em;
    margin: 0.15em;
    border: 1px solid #888;
    border-radius: 4px;
    background: #ddd;
    color: #333;
}
.smd_textile_bar--text {
    margin: 0;
}
.smd_textile_bar--text a {
    padding: 0.25em 0.65em;
    margin: 0.15em;
    color: #333;
    font-size:88.125%;
}
EOCSS;

        $aclass = array();
        $use_icons = get_pref('smd_textile_bar_icons');
        $aclass[] = (get_pref('smd_textile_bar_buttons')) ? 'smd_textile_bar--buttons' : '';
        $aclass[] = ($use_icons) ? 'smd_textile_bar--icons' : 'smd_textile_bar--text';
        $class_str = implode(' ', $aclass);

        foreach ($fields as $field) {
            $html = array();
            $html[] = '<div class="smd_textile_bar '.$class_str.'">';

            foreach ($buttons as $key => $opts) {
                if (!get_pref('smd_textile_bar_'.$key)) {
                    continue;
                }

                $params = array();

                foreach ($opts as $data => $val) {
                    $params[] = 'data-'.$data.'="'.$val.'"';
                }

                if ($use_icons) {
                    $content = '&nbsp;';
                    $title = ' title="'.gTxt('smd_textile_bar_btn_'.$key).'"';
                } else {
                    $content = gTxt('smd_textile_bar_btn_'.$key);
                    $title = '';
                }

                $html[] = '<a class="smd_textile_bar_btn"'.$title.' href="#'.$field.'" '.implode(' ', $params).'>'.$content.'</a>';

            }

            $html[] = '</div>';
            $html_str = implode(n, $html);
            $js .=
                '$(document).ready(function(){'.
                    '$("textarea#'.escape_js($field).'").before("'.escape_js($html_str).'")'.
                '});';
        }

        $js .= <<<EOF

(function($, len, createRange, duplicate){

    var opt = {}, is = {}, form = {}, words = {}, lines = {};

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

                $.each(opt.field.val().split(/\\r\\n|\\r|\\n/), function(index, line){

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

                if (!format[opt.callback]){
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

        /*!
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

            if (typeof start != "undefined"){

                this[0].selectionStart = start;
                this[0].selectionEnd = end;
                this[0].focus();
                return this;
            }

            else {

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

            if (jQuery.inArray(s, ['h1.', 'h2.', 'h3.', 'h4.', 'h5.', 'h6.']) >= 0) {
                s = opt.level;
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
         * Formats a image
         */

        image : function() {
        },

        /**
         * Formats a link
         */

        link : function() {

            var text = opt.selection.text;
            var link = 'http://';

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
            }

            else if (text.indexOf('www.') == 0) {
                link = 'http://'+text;
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

            if (is.empty) {

                if (
                    words.text.length == 1 &&
                    words.text[0].length >= 3 &&
                    /[:lower:]/.test(words.text[0]) === false
                ) {
                    abc = words.text[0];
                }

                else {
                    text = words.text.join(' ');
                }

                opt.selection.start = words.start;
                opt.selection.end = words.end;
            }

            insert(abc+'('+text+')');
        }
    };

    $.fn.smd_textile_bar = function(method) {

        if (methods[method]){
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        }

        else if (typeof method === 'object' || !method){
            return methods.init.apply(this, arguments);
        }

        else {
            $.error('[smd_textile_bar: unknown method '+method+']');
        }
    };

})(jQuery, 'length', 'createRange', 'duplicate');

$(document).ready(function(){
    $("a.smd_textile_bar_btn").smd_textile_bar();
});

EOF;

    echo '<style>' . $style . '</style>';
    echo script_js($js);
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
}

# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---
h1. smd_textile_bar

p. Brings a simple and minimal Quicktags Textile inserting bar to the backend. No, it's not an heavy and buggy WYSIWYG editor that screws your own XHTML input, but instead it's just a simple bar that insert Textile where you want. The Javascript code is forked from "hak_textile_tags by great hakjoon.":http://forum.textpattern.com/viewtopic.php?id=7470

h2. List of features

* A simple Textile inserting bar, offering the most common formatting options Textile has.
* Easy to use and install: just run the automated plugin installation and activate.
* Options can be configured via easy graphical user interface, located at Textpattern's Preferences panel (Textpattern / Admin / Preferences / Textile Bar).

h2. Requirements

p. Minimum requirements:

* Textpattern 4.2.0 or newer.
* A web browser that is "jQuery":http://jquery.com/ compatible

p. Recommended:

* Textpattern 4.4.1+

h2. Installation and usage

p. Download and copy the plugin code to the plugin installer textarea and run the automatic setup. After activating the plugin, you will see the textilebar above Body-textarea in the Write-tab.

# --- END PLUGIN HELP ---
-->
<?php
}
?>