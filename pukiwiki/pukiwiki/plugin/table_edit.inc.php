<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// Max pages allowed to be included at a time

define('PLUGIN_TABLE_EDIT_MAX', 4);
define('PLUGIN_TABLE_EDIT_FIELD_WIDTH', 100);
define('PLUGIN_TABLE_EDIT_FORCE_TIME_UPDATE', false);
define('PLUGIN_TABLE_EDIT_DEFAULT_SORT_STRING', ''); // semicolon(:) separated

//define('PLUGIN_TABLE_EDIT_STRING_ADD',          'add');
//define('PLUGIN_TABLE_EDIT_STRING_EDIT',         'edit');
//define('PLUGIN_TABLE_EDIT_STRING_UPDATE',       'update');
//define('PLUGIN_TABLE_EDIT_STRING_DELETE',       'delete');
//define('PLUGIN_TABLE_EDIT_STRING_DELETE_CHECK', 'delete');
//define('PLUGIN_TABLE_EDIT_STRING_COPY',         'copy');

define('PLUGIN_TABLE_EDIT_STRING_ADD',          '追加');
define('PLUGIN_TABLE_EDIT_STRING_EDIT',         '編集');
define('PLUGIN_TABLE_EDIT_STRING_UPDATE',       '更新');
define('PLUGIN_TABLE_EDIT_STRING_DELETE',       '削除');
define('PLUGIN_TABLE_EDIT_STRING_DELETE_CHECK', '削除の確認');
define('PLUGIN_TABLE_EDIT_STRING_COPY',         '新しい行として追加');

//define('PLUGIN_TABLE_EDIT_PLUGIN_NAME', 'read');
define('PLUGIN_TABLE_EDIT_PLUGIN_NAME', 'table_edit');

// ----
define('PLUGIN_TABLE_EDIT_USAGE', '#include(): Usage: (a-page-name-you-want-to-edit)');


class EditableTablePage extends Element
{
	var $pageElements;   // table or string

	function EditableTablePage() {
		parent::Element();
		$this->pageElements = array();
	}
	function add($obj) {
		$this->pageElements[] = & $obj;
	}

	function toString() {
		$str = '';
		foreach ($this->pageElements as $i) {
			$str .= is_a($i, 'EditableTable') || is_a($i, 'EditableYTable')? $i->toString() : $i;
		}
		return $str;
	}
}

class EditableTableCell extends Element
{
	var $rawtext;

	function EditableTableCell($text, $is_template = FALSE)
	{
		parent::Element();
		$this->rawtext = $text;
	}

	function cellString()
	{
		$text = $this->rawtext;
		while (preg_match('/^(?:(LEFT|CENTER|RIGHT)|(BG)?COLOR\(([#\w]+)\)|SIZE\((\d+)\)):(.*)$/',
		    $text, $matches)) {
			if ($matches[1]) {
				$text = $matches[5];
			} else if ($matches[3]) {
				$text = $matches[5];
			} else if ($matches[4]) {
				$text = $matches[5];
			}
		}
		if (substr($text, 0, 1) == '~') {
			$text = substr($text, 1);
		}

		if ($text != '' && $text{0} == '#') {
			$obj = & Factory_Div($this, $text);
			if (is_a($obj, 'Paragraph'))
				$obj = & $obj->elements[0];
		} else {
			$obj = & Factory_Inline($text);
		}
		return $obj->toString();
	}
}

class EditableTableEditCell extends Element
{
	function EditableTableEditCell($text, $link)
	{
		parent::Element();
		$this->rawtext = '[[' . $text . ':' . $link . ']]';
	}
	function toString()
	{
		return $this->rawtext;
	}

}

// | title1 | title2 | title3 |
// | cell1  | cell2  | cell3  |
// | cell4  | cell5  | cell6  |
class EditableTable extends Element
{
	var $type;
	var $types;
	var $col; // number of column

	function EditableTable($out)
	{
		parent::Element();

		$cells	= explode('|', $out[1]);
		$this->col   = count($cells);
		$this->type  = strtolower($out[2]);
		$this->types = array($this->type);
		$is_template = ($this->type == 'c');
		$row = array();
		foreach ($cells as $cell)
			$row[] = & new EditableTableCell($cell, $is_template);
		$this->elements[] = $row;
	}

	function canContain(& $obj)
	{
		return is_a($obj, 'EditableTable');
	}

	function & addRow(& $obj, $sort_str)
	{
		if ($sort_str == '')
			$this->insert($obj);
		else
			plugin_table_edit_insert($this, $obj, $sort_str);
		return $this;
	}

	function & insert(& $obj)
	{
		$this->elements[] = $obj->elements[0];
		$this->types[]    = $obj->type;
		return $this;
	}

	function getMaxNumberOfCols()
	{
		$r = 0;
		foreach ($this->elements as $row) {
			$col = count($row);
			if ($col > $r) $r = $col;
		}
		return $r;
	}

	function toString()
	{
		$str = '';
		$r = 0;
		foreach ($this->elements as $row) {
			$str .= '|';
			foreach ($row as $i) {
				$str .= $i->rawtext . '|';
			}
			$str .= $this->types[$r++] . "\n";
		}
		return $str;
	}
}

class EditableYTableCell extends Element
{
	var $rawtext;

	function EditableYTableCell($str)
	{
		parent::Element();
		$this->rawtext = $str;
	}
	function cellString()
	{
		return $this->rawtext;
	}
	function toString()
	{
		return preg_match('/^[^,"]+$/', $this->rawtext)? 
		       $this->rawtext : '"' . str_replace('"', '""', $this->rawtext) . '"';
	}
}

// , title1 , title2 , title3
// , cell1  , cell2  , cell3
// , cell4  , cell5  , cell6
class EditableYTable extends Element
{
	var $col;

	function EditableYTable($line)
	{
		parent::Element();

		$cells = csv_explode(',', substr($line, 1));
		$this->col = count($cells);

		// fix the last cell for all row.
		$last_cell = $cells[$this->col - 1];
		preg_match('/^(.*)$/', $last_cell, $matches);
		$cells[$this->col - 1] = $last_cell = $matches[0];
		$len = strlen($last_cell);
		if ($len > 2 && $last_cell{0} == '"' && $last_cell{$len - 1} == '"')
			$cells[$this->col - 1] = str_replace('""', '"', substr($last_cell, 1, -1));

		$row = array();
		foreach($cells as $cell) {
			if ($cell == '""')
				$cell = '';
			$row[] = & new EditableYTableCell($cell);
		}
		$this->elements[] = $row;
	}

	function canContain(& $obj)
	{
		return is_a($obj, 'EditableYTable');
	}

	function & addRow(& $obj, $sort_str)
	{
		if ($sort_str == '')
			$this->insert($obj);
		else
			plugin_table_edit_insert($this, $obj, $sort_str);
		return $this;
	}

	function & insert(& $obj)
	{
		$this->elements[] = $obj->elements[0];
		return $this;
	}

	function toString()
	{
		$str = '';
		foreach ($this->elements as $row) {
			foreach ($row as $i) {
				$str .= ',' . $i->toString();
			}
			$str .= "\n";
		}
		return $str;
	}
}

function & create_editable_table_page($lines, $from_page, $sort_str='', $append_edit_cell=false)
{
	global $vars, $digest;
	static $contents_id = 0;

	// Set digest
	$digest = md5(join('', get_source($vars['page'])));

	if (! is_array($lines)) $lines = explode("\n", $lines);

	if (PKWK_READONLY) $append_edit_cell = false;

	$tbpage = new EditableTablePage();
	$table = null;
	while (! empty($lines)) {
		$line = array_shift($lines);
		if ($line[0] == '/' && strlen($line) >= 2 && $line[1] == '/') { // comment line
			// nothing to do
		} else if ($line[0] == ',' && $line != ',') { // comma(',') type table
			$global_cnt++;
			$new_table = new EditableYTable($line);

			if ($append_edit_cell) {
				$cnt = count($table->elements) + 1;
				$table_number = count($tbpage->pageElements);
				if (strtolower($out[2]) == 'h' || strtolower($out[2]) == 'f') {
					$new_table->elements[0][] = & new EditableTableEditCell(PLUGIN_TABLE_EDIT_STRING_ADD, get_script_uri()  . '?cmd=' . PLUGIN_TABLE_EDIT_PLUGIN_NAME . '&mode=edit&page=' . rawurlencode($vars['page']) . '&table=' . $table_number . '&row=-1&from=' . urlencode($from_page));
				} else {
					$new_table->elements[0][] = & new EditableTableEditCell(PLUGIN_TABLE_EDIT_STRING_EDIT, get_script_uri() . '?cmd=' . PLUGIN_TABLE_EDIT_PLUGIN_NAME . '&mode=edit&page=' . rawurlencode($vars['page']) . '&table=' . $table_number . '&row=' . ($cnt-1) . '&from=' . urlencode($from_page));
				}
			}

			if ($table && $table->col == $new_table->col) {
				$table->addRow($new_table, $sort_str);
			} else {
				if ($table) $tbpage->add($table);  // num of col is not same.
				$table = $new_table;
			}
		} else if (preg_match('/^\|(.+)\|([hHfFcC]?)$/', $line, $out)) { // bar('|') type table
			$global_cnt++;
			$new_table = new EditableTable($out);

			if ($append_edit_cell) {
				$cnt = count($table->elements) + 1;
				$table_number = count($tbpage->pageElements);
				if (strtolower($out[2]) == 'h' || strtolower($out[2]) == 'f') {
					$new_table->elements[0][] = & new EditableTableEditCell(PLUGIN_TABLE_EDIT_STRING_ADD, get_script_uri()  . '?cmd=' . PLUGIN_TABLE_EDIT_PLUGIN_NAME . '&mode=edit&page=' . rawurlencode($vars['page']) . '&table=' . $table_number . '&row=-1&from=' . urlencode($from_page));
				} else {
					$new_table->elements[0][] = & new EditableTableEditCell(PLUGIN_TABLE_EDIT_STRING_EDIT, get_script_uri() . '?cmd=' . PLUGIN_TABLE_EDIT_PLUGIN_NAME . '&mode=edit&page=' . rawurlencode($vars['page']) . '&table=' . $table_number . '&row=' . ($cnt-1) . '&from=' . urlencode($from_page));
				}
			}

			if ($table && $table->col == $new_table->col) {
				$table->addRow($new_table, $sort_str);
			} else {
				if ($table) $tbpage->add($table);  // num of col is not same.
				$table = $new_table;
			}
		} else {
			if ($table == null) {
				// first block is a text.
				$tbpage->add($line);
			} else {
				// a teble follows a text.
				$tbpage->add($table);
				$tbpage->add($line);
				$table = null;
			}
		}
	}
	if ($table != null) {
		  $tbpage->add($table);
	}

	if ($append_edit_cell) {
		for ($i = 0; $i < count($tbpage->pageElements); $i++) {
			$t = & $tbpage->pageElements[$i];
			if (is_a($t, 'EditableTable') || is_a($t, 'EditableYTable')) {
				for ($j = 0; $j < count($t->elements); $j++) {
				}
			}
		}
	}
	return $tbpage;
}

function plugin_table_edit_cmp($x, $y, $sort_col_str)
{
	$a = split(":", $sort_col_str);

	for ($i = 0; $i < count($a); ++$i) {
		if ( ! preg_match('/^([0-9]+)([rns]+)?$/i', trim($a[$i]), $matches)) return 0;
		$sort_col  = intval($matches[1]) - 1;
		$tmp       = strtolower($matches[2]);
		$sort_num = (strpos($tmp, 'n') !== false);
		$sort_rev = (strpos($tmp, 'r') !== false);

		$x_key  = $x[$sort_col]->rawtext;
		$y_key  = $y[$sort_col]->rawtext;
		$cmp = $sort_num? intval($x_key) - intval($y_key) : strcmp($x_key, $y_key);
		if ($cmp == 0) {
			continue;
		} else {
			return $cmp * ($sort_rev? -1 : 1);
		}
	}
	return 0;
}

function plugin_table_edit_insert(& $table, $row_elem, $sort_str)
{
	$row  = $row_elem->elements[0];
	$type = $row_elem->types[0];

	if ($sort_str == '') {
		$i = count($table->elements);
	} else {
		for ($i = 0; $i < count($table->elements); ++$i) {
			$t = $table->types[$i];
			if ($type == '') {
				if ($t == '' && plugin_table_edit_cmp($row, $table->elements[$i], $sort_str) < 0)
					break;
			} else {
				break;
			}
		}
	}

	$table->elements[] = null;
	for ($j = count($table->elements) - 1; $i < $j; --$j) {
		$table->elements[$j] = $table->elements[$j - 1];
	}
	$table->elements[$i] = $row;

	$table->types[] = null;
	for ($j = count($table->types) - 1; $i < $j; --$j) {
		$table->types[$j] = $table->types[$j - 1];
	}
	$table->types[$i] = $type;
}

function plugin_table_edit_convert()
{
	global $script, $vars, $get, $post, $menubar, $_msg_include_restrict;
	static $included = array();
	static $count = 1;

	if (func_num_args() == 0 || func_num_args() >= 3) return PLUGIN_TABLE_EDIT_USAGE . '<br />' . "\n";

	// $menubar will already be shown via menu plugin
	if (! isset($included[$menubar])) $included[$menubar] = TRUE;

	// Loop yourself
	$root = isset($vars['page']) ? $vars['page'] : '';
	$included[$root] = TRUE;

	// Get arguments
	$args = func_get_args();
	// strip_bracket() is not necessary but compatible
	$page = get_fullname(strip_bracket($args[0]), $root);
	$sort_str = (count($args)>=2)? $args[1] : PLUGIN_TABLE_EDIT_DEFAULT_SORT_STRING;

	$s_page = htmlsc($page);
	$r_page = rawurlencode($page);
	$link = '<a href="' . $script . '?' . $r_page . '">' . $s_page . '</a>'; // Read link

	// I'm stuffed
	if (isset($included[$page])) {
		return '#include(): Included already: ' . $link . '<br />' . "\n";
	} if (! is_page($page)) {
		return '#include(): No such page: ' . $s_page . '<br />' . "\n";
	} if ($count > PLUGIN_TABLE_EDIT_MAX) {
		return '#include(): Limit exceeded: ' . $link . '<br />' . "\n";
	} else {
		++$count;
	}

	// One page, only one time, at a time
	$included[$page] = TRUE;

	// Include A page, that probably includes another pages
	$get['page'] = $post['page'] = $vars['page'] = $page;
	if (check_readable($page, false, false)) {
		$tblpage = & create_editable_table_page(get_source($page), $root, $sort_str, true);
		if ($tblpage != '') {
			$body = convert_html($tblpage->toString());
		} else {
			$body = 'CANNOT CONVERT PAGE: ' . $page;
		}
	} else {
		$body = str_replace('$1', $page, $_msg_include_restrict);
	}
	$get['page'] = $post['page'] = $vars['page'] = $root;

	return $body;
}


function plugin_table_edit_action_post($page, $root, $table_id, $row)
{
	global $vars;
	$tblpage = & create_editable_table_page(get_source($page), $root);
	$table = & $tblpage->pageElements[$table_id];
	if ($row > count($table->elements) || $row < -1) return null;

	if ($row == -1 || $vars['copy'] == 'on') {
		$rowcell = array();
	 	for ($i = 0; isset($vars['cell_' . ($i+1)]) ; $i++) {
	 		$str = $vars['cell_' . ($i+1)];
			$rowcell[] = is_a($table, 'EditableTable')? new EditableTableCell($str, false) : new EditableYTableCell($str);
		}
		$table->elements[] = $rowcell;
	} else {
	 	$rowcells = $table->elements[$row];
	 	for ($i = 0; isset($vars['cell_' . ($i+1)]) ; $i++) {
	 		$str = $vars['cell_' . ($i+1)];
	 		$rowcells[$i]->rawtext = $str;
	 	}
	}
	return $tblpage->toString();
}

function plugin_table_edit_action_delete($page, $root, $table_id, $row)
{
	$tblpage = & create_editable_table_page(get_source($page), $root);
	$table = & $tblpage->pageElements[$table_id];
	if ($row > count($table->elements) || $row < 0) return null;

	unset($table->elements[$row]);
	return $tblpage->toString();
}

function plugin_table_edit_action_edit($page, $from_page, $table_id, $row, $digest)
{
	global $vars, $_btn_notchangetimestamp;
	$tblpage = & create_editable_table_page(get_source($page), $from_page);

	$body = '<form action="' . get_script_uri() . '?cmd=' . PLUGIN_TABLE_EDIT_PLUGIN_NAME . '&mode=post&from=' . urlencode($from_page) . '&table=' . $table_id . '&row=' . $row . '&page=' . rawurlencode($vars['page']) . '&digest=' . $digest . '" method="post">';
	$body .= $root;
	$j = 0;
	$table = & $tblpage->pageElements[$table_id];

	for ($i = 0; $i < count($table->elements); $i++) {
		if (strtolower($table->types[$i]) == 'h' || strtolower($table->types[$i]) == 'f') {
			$header = & $table->elements[$i];
			break;
		}
	}

	$length = count($table->elements[0]);
	for ($i = 0; $i < $length; $i++) {
		$body .= ($i+1) . ': ' . ($header? $header[$i]->cellString() : '') . '<br />';
		$body .= ' &nbsp; &nbsp; <input type="text" size="' . PLUGIN_TABLE_EDIT_FIELD_WIDTH . '" name="cell_' . ($i+1) . '" value=';
		$body .= preg_match('/^[^"]+$/', $table->elements[$row][$i]->rawtext)?
				'"' . $table->elements[$row][$i]->rawtext . '"' : "'" . $table->elements[$row][$i]->rawtext . "'";
		$body .= '></input><br />';
	}

	if ($row == -1) {
		$body .= '<br /> &nbsp; <input type="submit" value="' . PLUGIN_TABLE_EDIT_STRING_ADD . '"></form>';
	} else {
		$body .= '<br /> &nbsp; <input type="submit" value="' . PLUGIN_TABLE_EDIT_STRING_UPDATE . '">';
		if ( ! PLUGIN_TABLE_EDIT_FORCE_TIME_UPDATE)
			$body .= ' &nbsp; <input type="checkbox" name="noupdatetime"> ' . $_btn_notchangetimestamp . ' </input>';
		$body .= ' &nbsp; <input type="checkbox" name="copy"> ' . PLUGIN_TABLE_EDIT_STRING_COPY . ' </input></form>';

		$body .= '<form action="' . get_script_uri() . '?cmd=' . PLUGIN_TABLE_EDIT_PLUGIN_NAME . '&mode=delete&from=' . urlencode($from_page) . '&table=' . $table_id . '&row=' . $row . '&page=' . rawurlencode($vars['page'])  . '&digest=' . $digest . '" method="post">';
		$body .= '<br /> &nbsp; <input type="submit" value="' . PLUGIN_TABLE_EDIT_STRING_DELETE . '">';
		$body .= ' &nbsp; <input type="checkbox" name="delete"> ' . PLUGIN_TABLE_EDIT_STRING_DELETE_CHECK . ' </input></form>';

	}
	return $body;
}

function plugin_table_check_collision($page, $digest, $src)
{
	global $_title_collided, $_msg_collided_auto, $_msg_collided, $do_update_diff_table;

	$newsrc = join('', get_source($page));
	$newdigest = md5($newsrc);
	if ($digest != $newdigest) {
		$vars['digest'] = $newdigest; // Reset
		list($postdata_input, $auto) = do_update_diff($newsrc, $msg, $src);

		$retvars['msg' ] = $_title_collided;
		$retvars['body'] = ($auto ? $_msg_collided_auto : $_msg_collided) . "\n";
		$retvars['body'] .= $do_update_diff_table;
		$retvars['body'] .= edit_form($page, $newsrc, $newdigest, FALSE);
		return $retvars;
	}
	return null;
}

//function plugin_read_action()
function plugin_table_edit_action()
{
	global $vars, $referer;

	$refer  = isset($vars['page'])   ? $vars['page']   : '';
	$from   = isset($vars['from'])   ? $vars['from']   : '';
	$table  = isset($vars['table'])  ? $vars['table']  : '';
	$digest = isset($vars['digest']) ? $vars['digest'] : '';
	$page   = get_fullname(strip_bracket(array_shift($args)), $refer);

	$mode = isset($vars['mode'])? strtolower($vars['mode']) : '';
	$row  = isset($vars['row'])? $vars['row'] : '0';

	if ($mode=='edit') {
		if (PKWK_READONLY)
			return array('msg'=>$_title_add, 'body' => PLUGIN_TABLE_EDIT_PLUGIN_NAME . ': PKWK_READONLY prohibits editing.<br />');
		check_editable($page, true, true);
		$src = join('', get_source($page));
		$digest = md5($src);
		$body = plugin_table_edit_action_edit($page, $from, $table, $row, $digest);
		return array('msg'=>$_title_add, 'body' => $body);
	} else if ($mode=='delete' || $mode=='post') {
		if (PKWK_READONLY)
			return array('msg'=>$_title_add, 'body' => PLUGIN_TABLE_EDIT_PLUGIN_NAME . ': PKWK_READONLY prohibits changing.<br />');
		check_editable($page, true, true);

		if ($mode=='delete' && $vars['delete'] != 'on')
			return array('msg'=>$_title_add, 'body' => PLUGIN_TABLE_EDIT_PLUGIN_NAME . ': delete check-box is not checked.<br />');

		$src = ($mode=='delete')? plugin_table_edit_action_delete($page, $from, $table, $row) : plugin_table_edit_action_post($page, $from, $table, $row);
		if ($src == null)
			return array('msg'=>$_title_add, 'body' => PLUGIN_TABLE_EDIT_PLUGIN_NAME . ': invalid args.<br />');

		$result = plugin_table_check_collision($page, $digest, $src);
		if ($result != null) return $result;

		$notimeupdate = $vars['noupdatetime'] == 'on' && ! PLUGIN_TABLE_EDIT_FORCE_TIME_UPDATE;
		page_write($page, $src, $notimeupdate);
		header('Status: 301 Moved Permanently');
		header('Location: ' . $script . '?' . urlencode($from)); // HTTP
		die();
	} else { // This is only invoked when this plug-in uses as read.inc.php.
		if (is_page($page)) {
			check_readable($page, true, true);
			header_lastmod($page);
			$tblpage = & create_editable_table_page(get_source($page), $refer, PLUGIN_TABLE_EDIT_DEFAULT_SORT_STRING, ! is_freeze($page));
			$body = convert_html($tblpage->toString());
			if ($trackback || $referer) {
				// Referer functionality uses trackback functions
				// without functional reason now
				if ($trackback) $body .= tb_get_rdf($page); // Add TrackBack-Ping URI
				if ($referer) ref_save($page);
			}
			return array('msg'=>$_title_add, 'body' => $body);
		} else if (is_pagename($page)) {
			$vars['cmd'] = 'edit';
			return do_plugin_action('edit');
		} else {
			return array('msg'=>$_title_invalidwn,
					'body'=>str_replace('$1', htmlsc($page),
						str_replace('$2', 'WikiName', $_msg_invalidiwn)));
		}
	}
}

?>
