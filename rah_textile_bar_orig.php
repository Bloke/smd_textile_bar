<?php

/**
 * Rah_textile_bar plugin for Textpattern CMS
 *
 * @author Jukka Svahn
 * @date 2008-
 * @license GNU GPLv2
 * @link http://rahforum.biz/plugins/rah_textile_bar
 *
 * Copyright (C) 2012 Jukka Svahn <http://rahforum.biz>
 * Licensed under GNU Genral Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

	if(@txpinterface == 'admin') {
		rah_textile_bar::install();
		add_privs('plugin_prefs.rah_textile_bar', '1,2');
		register_callback(array('rah_textile_bar', 'prefs'), 'plugin_prefs.rah_textile_bar');
		register_callback(array('rah_textile_bar', 'install'), 'plugin_lifecycle.rah_textile_bar');
		register_callback(array('rah_textile_bar', 'head'), 'admin_side', 'head_end');
	}

class rah_textile_bar {

	static public $version = '0.7';

	/**
	 * Installer
	 * @param string $event Admin-side event.
	 * @param string $step Admin-side event, plugin-lifecycle step.
	 */

	static public function install($event='', $step='') {
		
		global $prefs;
		
		if($step == 'deleted') {
			
			safe_delete(
				'txp_prefs',
				"name like 'rah\_textile\_bar\_%'"
			);
			
			return;
		}
		
		$current = isset($prefs['rah_textile_bar_version']) ? 
			$prefs['rah_textile_bar_version'] : 'base';
		
		if($current == self::$version)
			return;
		
		$position = 230;

		$values = self::buttons();
		$values[] = 'excerpt';
		$values[] = 'body';
		
		foreach($values as $n) {
			
			$name = 'rah_textile_bar_'.$n;
			
			if(!isset($prefs[$name])) {
				safe_insert(
					'txp_prefs',
					"prefs_id=1,
					name='".doSlash($name)."',
					val='1',
					type=1,
					event='rah_txtbar',
					html='yesnoradio',
					position=".$position
				);
				
				$prefs[$name] = $val;
			}
			
			$position++;
		}

		safe_delete(
			'txp_prefs',
			"name LIKE 'rah\_textile\_bar\_h_' OR name='rah_textile_bar_codeline'"
		);
		
		set_pref('rah_textile_bar_version', self::$version,'rah_txtbar',2,'',0);
		$prefs['rah_textile_bar_version'] = self::$version;
	}

	/**
	 * Lists buttons
	 * @return array Array of buttons.
	 */

	static public function buttons() {
		return array(
			'strong',
			'link',
			'emphasis',
			'ins',
			'del',
			'heading',
			'image',
			'bc',
			'ul',
			'ol',
			'bq',
			'pre',
			'acronym',
			'sup',
			'sub',
		);
	}

/**
 * All the required scripts and styles
 */

	static public function head() {
		global $event, $prefs;
		
		if($event != 'article')
			return;
		
		$fields = array('body', 'excerpt');
		
		foreach($fields as $key => $field) {
			if(empty($prefs['rah_textile_bar_'.$field])) {
				unset($fields[$key]);
			}
		}
		
		if(!empty($prefs['rah_textile_bar_additional_fields'])) {
			$fields += do_list($prefs['rah_textile_bar_additional_fields']);
		}

		$js = '';

		foreach($fields as $field) {
		
			$html = <<<EOF
				<div class="rah_textile_bar">
					<a class="rah_textile_btn" href="#{$field}" data-callback="inline" data-before="*" data-after="*">Strong</a>
					<a class="rah_textile_btn" href="#{$field}" data-callback="inline" data-before="_" data-after="_">Em</a>
					<a class="rah_textile_btn" href="#{$field}" data-callback="inline" data-before="+" data-after="+">Ins</a>
					<a class="rah_textile_btn" href="#{$field}" data-callback="inline" data-before="-" data-after="-">Del</a>
					<a class="rah_textile_btn" href="#{$field}" data-callback="link">www</a>
					<a class="rah_textile_btn" href="#{$field}" data-callback="list" data-bullet="*">Ul</a>
					<a class="rah_textile_btn" href="#{$field}" data-callback="code" data-before="@" data-after="@">Code</a>
					<a class="rah_textile_btn" href="#{$field}" data-callback="block" data-tag="bq">Blockquote</a>
					<a class="rah_textile_btn" href="#{$field}" data-callback="block" data-tag="pre">Pre</a>
					<a class="rah_textile_btn" href="#{$field}" data-callback="heading" data-level="h2">h#.</a>
					<a class="rah_textile_btn" href="#{$field}" data-callback="acronym">ABC</a>
				</div>
EOF;

			$js .= 
				'$(document).ready(function(){'.
					'$("textarea#'.escape_js($field).'").before("'.escape_js($html).'")'.
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
					if(attr.name.indexOf('data-') === 0) {
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
					
					if(ls > opt.selection.end) {
						return;
					}
				
					le = ls+line.length;
						
					if(le >= opt.selection.start) {
						
						if(!lines.text[0]) {
							lines.start = ls;
						}
						
						lines.text.push(line);
						lines.end = le;
					}
					
					ls = le+1;

					$.each(line.split(' '), function(index, w) {
						
						if(i > opt.selection.end) {
							return;
						}
						
						if(i+w.length >= opt.selection.start) {
							
							if(!words.text[0]) {
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
				
				if(!format[opt.callback]){
					return;
				}
					
				var f = format[opt.callback]();
				
				if(f) {
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
			
			var start, end, t = this[0], browser = $.browser.msie;
			
			if(
				typeof options === "object" && 
				typeof options.start === "number" && 
				typeof options.end === "number"
			) {
				start = options.start;
				end = options.end;
			}
			
			if(typeof start != "undefined"){
				
				if(browser){
					var selRange = this[0].createTextRange();
					selRange.collapse(true);
					selRange.moveStart('character', start);
					selRange.moveEnd('character', end-start);
					selRange.select();
				}
				
				else {
					this[0].selectionStart = start;
					this[0].selectionEnd = end;
				}
				
				this[0].focus();
				return this;
			}
			
			else {
			
				if(browser){
					
					var selection = document.selection;
					
					if (this[0].tagName.toLowerCase() != "textarea") {
						var val = this.val(),
						range = selection[createRange]()[duplicate]();
						range.moveEnd("character", val[len]);
						var s = (range.text == "" ? val[len]:val.lastIndexOf(range.text));
						range = selection[createRange]()[duplicate]();
						range.moveStart("character", -val[len]);
						var e = range.text[len];
					}
					
					else {
						var range = selection[createRange](),
						stored_range = range[duplicate]();
						stored_range.moveToElementText(this[0]);
						stored_range.setEndPoint('EndToEnd', range);
						var s = stored_range.text[len] - range.text[len],
						e = s + range.text[len]
					}
				}
			
				else {
					var s = t.selectionStart, 
					e = t.selectionEnd;
				}
			
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
		
		if(typeof start === "undefined") {
			start = opt.selection.start;
		}
		
		if(typeof end === "undefined") {
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
			
			if(
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
			
			if(
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
			
			if(jQuery.inArray(s, ['h1.', 'h2.', 'h3.', 'h4.', 'h5.', 'h6.']) >= 0) {
				s = s == 'h6.' ? 1 : parseInt(s.substr(1,1)) + 1;
				insert(s, lines.start+1, lines.start+2);
				opt.selection.end = lines.start+line.length;
				return;
			}
			
			insert(
				opt.level +'. ' + line + (!is.paragraph ? "\\n\\n" : ''),
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
			
			if(
				is.empty &&
				words.text.length == 1
			) {
				opt.selection.start = words.start;
				opt.selection.end = words.end;
				text = words.text.join(' ');
			}
			
			if(text.indexOf('http://') == 0 || text.indexOf('https://') == 0) {
				link = text;
				text = '$';
			}
			
			else if(text.indexOf('www.') == 0) {
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

			if(is.empty) {
				
				if(
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

	$.fn.rah_textile_bar = function(method) {
		
		if(methods[method]){
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		}
		
		else if(typeof method === 'object' || !method){
			return methods.init.apply(this, arguments);
		}
		
		else {
			$.error('[rah_textile_bar: unknown method '+method+']');
		}
	};

})(jQuery, 'length', 'createRange', 'duplicate');

$(document).ready(function(){
	$("a.rah_textile_btn").rah_textile_bar();
});

EOF;

	echo script_js($js);
}

	/**
	 * Redirects to the preferences panel
	 */

	static public function prefs() {
		header('Location: ?event=prefs&step=advanced_prefs#prefs-rah_textile_bar_body');
		echo 
			'<p id="message">'.n.
			'	<a href="?event=prefs&amp;step=advanced_prefs#prefs-rah_textile_bar_body">'.gTxt('continue').'</a>'.n.
			'</p>';
	}
}

?>