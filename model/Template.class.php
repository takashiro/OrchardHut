<?php

class Template{
	static public $SOURCE_DIR;

	static protected $StatementNext = 6;

	protected $type;
	protected $style;
	protected $name;

	function __construct($type, $style, $name){
		$this->type = $type;
		$this->style = $style;
		$this->name = $name;
	}

	public function lastModifiedTime(){
		$sourcePath = $this->getSourcePath();
		return file_exists($sourcePath) ? filemtime($sourcePath) : -1;
	}

	public function getType(){
		return $this->type;
	}

	public function getStyle(){
		return $this->name;
	}

	public function getName(){
		return $this->name;
	}

	public function getDirectoryPath(){
		return self::$SOURCE_DIR.$this->type.'/'.$this->style.'/';
	}

	public function getOriginalSourcePath(){
		return $this->getDirectoryPath().$this->name.'.htm';
	}

	public function getSourcePath(){
		$htmpath = $this->getDirectoryPath().$this->name.'.htm';
		if(!file_exists($htmpath)){
			$htmpath = self::$SOURCE_DIR.$this->type.'/default/'.$this->name.'.htm';

			if($this->type != 'user' && !file_exists($htmpath)){
				$htmpath = self::$SOURCE_DIR.'user/default/'.$this->name.'.htm';
			}
		}
		return $htmpath;
	}

	public function getTemplateRoot(){
		return './view/'.$this->type.'/'.$this->style.'/';
	}

	public function parse(){
		$template = file_get_contents($this->getSourcePath());
		if($template === false)
			exit("Current template file {$this->templateFile} not found or have no access!");

		$var_regexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
		$const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";

		$template = preg_replace("/([\n\r]+)\t+/s", "\\1", $template);
		$template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);
		$template = str_replace("{LF}", "<?=\"\\n\"?>", $template);

		$template = preg_replace("/\{(\\\$[a-zA-Z0-9_\[\]\'\"\$\.\x7f-\xff]+)\}/s", "<?=\\1?>", $template);

		$template = preg_replace_callback("/$var_regexp/s", 'Template::echovar', $template);
		$template = preg_replace_callback("/\<\?\=\<\?\=$var_regexp\?\>\?\>/s", 'Template::echovar', $template);

		$template = preg_replace_callback("/\{lang\s+([a-zA-Z0-9_]+?)\s+([a-zA-Z0-9_]+?)\}/is", function($matches){
			return lang($matches[1], $matches[2]);
		}, $template);

		//{template *template_name*}
		$template = preg_replace_callback("/[\n\r\t]*\{template\s+([a-z0-9_]+)\}[\n\r\t]*/is", 'Template::parse_subtemplate', $template);
		$template = preg_replace_callback("/[\n\r\t]*\{template\s+(.+?)\}[\n\r\t]*/i", 'Template::parse_subtemplate', $template);

		//{eval *expression*}
		$template = preg_replace_callback("/[\n\r\t]*\{eval\s+(.+?)\}[\n\r\t]*/is", function($matches){
			return Template::stripvtags('<? '.$matches[1].' ?>','');
		}, $template);

		//{echo *expression*}
		$template = preg_replace_callback("/[\n\r\t]*\{echo\s+(.+?)\}[\n\r\t]*/is", function($matches){
			return Template::stripvtags('<?php echo '.$matches[1].';?>','');
		}, $template);

		//{elseif}
		$template = preg_replace_callback("/([\n\r\t]*)\{elseif\s+(.+?)\}([\n\r\t]*)/is", function($matches){
			return Template::stripvtags($matches[1].'<? } elseif('.$matches[2].') { ?>'.$matches[3],'');
		}, $template);

		//{else}
		$template = preg_replace("/([\n\r\t]*)\{else\}([\n\r\t]*)/is", "\\1<? } else { ?>\\2", $template);

		//{loop $var $key $value}
		for($i = 0; $i < self::$StatementNext; $i++) {
			$template = preg_replace_callback("/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\}[\n\r]*(.+?)[\n\r]*\{\/loop\}[\n\r\t]*/is", function($matches){
				return Template::stripvtags('<? if(is_array('.$matches[1].')) { foreach('.$matches[1].' as '.$matches[2].') { ?>', $matches[3].'<? } } ?>');
			}, $template);
			$template = preg_replace_callback("/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*(.+?)[\n\r\t]*\{\/loop\}[\n\r\t]*/is", function($matches){
				return Template::stripvtags('<? if(is_array('.$matches[1].')) { foreach('.$matches[1].' as '.$matches[2].' => '.$matches[3].') { ?>', $matches[4].'<? } } ?>');
			}, $template);
			$template = preg_replace_callback("/([\n\r\t]*)\{if\s+(.+?)\}([\n\r]*)(.+?)([\n\r]*)\{\/if\}([\n\r\t]*)/is", function($matches){
				return Template::stripvtags($matches[1].'<? if('.$matches[2].') { ?>'.$matches[3], $matches[4].$matches[5].'<? } ?>'.$matches[6]);
			}, $template);
		}


		$th = $this;
		$template = preg_replace_callback("/\{__{$const_regexp}__\}/s", function($matches) use(&$th){
			$const = str_replace('_', '', $matches[1]);
			return $th->{'get'.$const}();
		}, $template);

		$template = preg_replace("/\{$const_regexp\}/s", "<?=\\1?>", $template);
		$template = preg_replace("/ \?\>[\n\r]*\<\? /s", " ", $template);

		$template = preg_replace_callback("/\"(http)?[\w\.\/:]+\?[^\"]+?&[^\"]+?\"/", 'Template::transamp', $template);

		$template = preg_replace_callback("/[\n\r\t]*\{block\s+([a-zA-Z0-9_]+)\}(.+?)\{\/block\}/is", 'Template::stripblock', $template);

		return $template;
	}

	static public function transamp($matches) {
		$str = str_replace('&', '&amp;', $matches[0]);
		$str = str_replace('&amp;amp;', '&amp;', $str);
		$str = str_replace('\"', '"', $str);
		return $str;
	}

	static public function echovar($matches) {
		return '<?='.str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $matches[1])).'?>';
	}

	static public function stripvtags($expr, $statement) {
		$expr = str_replace("\\\"", "\"", preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr));
		$statement = str_replace("\\\"", "\"", $statement);
		return $expr.$statement;
	}

	static public function stripblock($matches) {
		$var = &$matches[1];
		$s = &$matches[2];
		$s = str_replace('\\"', '"', $s);
		$s = preg_replace("/<\?=\\\$(.+?)\?>/", "{\$\\1}", $s);
		preg_match_all("/<\?=(.+?)\?>/e", $s, $constary);
		$constadd = '';
		$constary[1] = array_unique($constary[1]);
		foreach($constary[1] as $const) {
			$constadd .= '$__'.$const.' = '.$const.';';
		}
		$s = preg_replace("/<\?=(.+?)\?>/", "{\$__\\1}", $s);
		$s = str_replace('?>', "\n\$$var .= <<<EOF\n", $s);
		$s = str_replace('<?', "\nEOF;\n", $s);
		return "<?\n$constadd\$$var = <<<EOF\n".$s."\nEOF;\n?>";
	}

	static public function parse_subtemplate($matches){
		return "\n".file_get_contents(view($matches[1]))."\n";
	}

	/*
		The function generates a select element.
		$name is the name of the select element, as an identifier in its form.
		$option is an array whose values are the texts of each option and keys are the corresponding value submitted to server
		$selected is the value of the selected element. Its default value is NULL, not 0, for 0 equals(==) any string other than pure digits like "123"
	*/
	static public function select($name, $option, $selected = NULL){
		$html = '<select id="'.$name.'" name="'.$name.'">';
		if(!is_array($option)){
			$option = explode(',', $option);
		}
		foreach($option as $value => $text){
			$html.= '<option value="'.$value.'"'.($value == $selected ? ' selected="selected"' : '').'>'.$text.'</option>';
		}
		$html.= '</select>';

		return $html;
	}

	static public function radio($name, $option, $value = 0){
		$html = '';
		if(!is_array($option)){
			$option = explode(',', $option);
		}
		foreach($option as $key => $val){
			$html.= '<input type="radio" name="'.$name.'" value="'.$key.'"'.($value == $key ? ' checked="checked"' : '').' /><span class="checkbox">'.$val.'</span>';
		}

		return $html;
	}

	/*
		Function mpage (short for multiple page) generate HTML codes for multiple pages. It's a paging function.
		$totalnum is the total number of rows of data.
		$page is the current page number.
		$limit is the number of rows on each page.
		$url is the basic URL, that a new parameter "page" (via GET method), which represents the target page, will be appended to.
		@TO-DO: it does not support language packs, for chinese words are already written, as follows, inside the function.
	*/
	static public function mpage($totalnum, $page, $limit, $url = ''){
		if(!$url){
			$url = $_SERVER['SCRIPT_FILENAME'].($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '');
		}
		$delimeter = strpos($url, '?') !== false ? '&' : '?';

		$maxpage = ceil($totalnum / $limit);
		if($maxpage <= 1){
			return '';
		}

		$page = min($maxpage, max(1, intval($page)));

		$html = '<div class="mpage">';

		$html.= '<span class="total">'.$totalnum.'</span>';

		if($page > 1){
			if($page > 2){
				$html.= '<a href="'.$url.$delimeter.'page=1'.'">'."首页".'</a>';
			}
			$html.= '<a href="'.$url.$delimeter.'page='.max(1,$page - 1).'">'."上一页".'</a>';
		}

		$faraway = min($maxpage, $page + 5 + max(0, 6 - $page));
		for($i = max(1, $page - 5 - max(0,$page + 5 - $maxpage)); $i <= $faraway; $i++){
			if($i == $page){
				$html.= '<a href="###" class="current">'.$i.'</a>';
			}else{
				$html.= '<a href="'.$url.$delimeter.'page='.$i.'">'.$i.'</a>';
			}
		}

		if($page < $maxpage){
			$html.= '<a href="'.$url.$delimeter.'page='.($page + 1).'">'."下一页".'</a>';
			if($page < $maxpage - 1){
				$html.= '<a href="'.$url.$delimeter.'page='.$maxpage.'">'."尾页".'</a>';
			}
		}

		$html.= '</div>';

		return $html;
	}

	static public function checkbox($name, $tips, $value = false){
		return '<input type="checkbox" id="'.$name.'" name="'.$name.'"'.($value ? ' checked="checked"' : '').' /><label for="'.$name.'">'.$tips.'</label>';
	}

	static public function split($str, $span, $param = '-'){
		$i = $span = intval($span);
		$length = strlen($str);

		while($i < $length){
			$str = substr($str, 0, $i).$param.substr($str, $i);
			$i += $span + 1;
			$length++;
		}

		return $str;
	}

	static public function tselect($name, $formats, $components, $with_ext = true, $componentid = 0){
		$value = array($componentid);

		$find_parent = array();
		foreach($components as $c){
			$find_parent[$c['id']] = $c['parentid'];
		}

		$cur = $componentid;
		while(!empty($find_parent[$cur])){
			$cur = $find_parent[$cur];
			array_unshift($value, $cur);
		}

		$html = '<span class="tselect">';
		$html.= '<input type="hidden" class="value" name="'.$name.'" value="'.implode(',', $value).'" />';

		$format_components = array();
		foreach($components as &$c){
			$format_components[$c['formatid']][$c['id']] = array('name' => $c['name'], 'parentid' => $c['parentid']);
		}
		unset($c);

		$vi = 0;
		foreach($formats as &$f){
			$html.= '<select>';
			if(isset($format_components[$f['id']])){
				foreach($format_components[$f['id']] as $id => $c){
					@$html.= '<option value="'.$id.'" parentid="'.$c['parentid'].'"'.(isset($value[$vi]) && $id == $value[$vi] ? ' selected="selected"' : '').'>'.$c['name'].'</option>';
				}
			}
			$html.= '</select>';
			$vi++;
		}
		unset($f);

		if($with_ext){
			$html.= '<input type="text" class="ext" placeholder="更详细的单元及寝室号" />';
		}
		$html.= '</span>';

		return $html;
	}
}

Template::$SOURCE_DIR = S_ROOT.'view/';

?>
