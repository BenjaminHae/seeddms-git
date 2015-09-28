<?php
/**
 * Implementation of Timeline view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for Timeline view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Timeline extends SeedDMS_Bootstrap_Style {
		var $dms;
		var $folder_count;
		var $document_count;
		var $file_count;
		var $storage_size;

	function show() { /* {{{ */
		$this->dms = $this->params['dms'];
		$user = $this->params['user'];
		$data = $this->params['data'];
		$from = $this->params['from'];
		$to = $this->params['to'];
		$skip = $this->params['skip'];

		$this->htmlAddHeader('<link href="../styles/'.$this->theme.'/timeline/timeline.css" rel="stylesheet">'."\n", 'css');
		$this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/timeline/timeline-min.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/timeline/timeline-locales.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("timeline"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
?>

<?php
echo "<div class=\"row-fluid\">\n";

echo "<div class=\"span3\">\n";
$this->contentHeading(getMLText("timeline"));
echo "<div class=\"well\">\n";
?>
<form action="../out/out.Timeline.php" class="form form-inline" name="form1">
	<div class="control-group">
		<label class="control-label" for="startdate"><?= getMLText('date') ?></label>
		<div class="controls">
       <span class="input-append date" style="display: inline;" id="fromdate" data-date="<?php echo date('Y-m-d', $from); ?>" data-date-format="yyyy-mm-dd" data-date-language="<?php echo str_replace('_', '-', $this->params['session']->getLanguage()); ?>">
			 <input type="text" class="input-small" name="fromdate" value="<?php echo date('Y-m-d', $from); ?>"/>
				<span class="add-on"><i class="icon-calendar"></i></span>
			</span> -
      <span class="input-append date" style="display: inline;" id="todate" data-date="<?php echo date('Y-m-d', $to); ?>" data-date-format="yyyy-mm-dd" data-date-language="<?php echo str_replace('_', '-', $this->params['session']->getLanguage()); ?>">
			<input type="text" class="input-small" name="todate" value="<?php echo date('Y-m-d', $to); ?>"/>
				<span class="add-on"><i class="icon-calendar"></i></span>
			</span>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="skip"><?= getMLText('exclude_items') ?></label>
		<div class="controls">
			<input type="checkbox" name="skip[]" value="add_file" <?= ($skip &&  in_array('add_file', $skip)) ? 'checked' : '' ?>> <?= getMLText('timeline_skip_add_file') ?><br />
			<input type="checkbox" name="skip[]" value="status_change_0" <?= ($skip && in_array('status_change_0', $skip)) ? 'checked' : '' ?>> <?= getMLText('timeline_skip_status_change_0') ?><br />
			<input type="checkbox" name="skip[]" value="status_change_1" <?= ($skip && in_array('status_change_1', $skip)) ? 'checked' : '' ?>> <?= getMLText('timeline_skip_status_change_1') ?><br />
			<input type="checkbox" name="skip[]" value="status_change_2" <?= ($skip && in_array('status_change_2', $skip)) ? 'checked' : '' ?>> <?= getMLText('timeline_skip_status_change_2') ?><br />
			<input type="checkbox" name="skip[]" value="status_change_3" <?= ($skip && in_array('status_change_3', $skip)) ? 'checked' : '' ?>> <?= getMLText('timeline_skip_status_change_3') ?><br />
			<input type="checkbox" name="skip[]" value="status_change_-1" <?= ($skip && in_array('status_change_-1', $skip)) ? 'checked' : '' ?>> <?= getMLText('timeline_skip_status_change_-1') ?><br />
			<input type="checkbox" name="skip[]" value="status_change_-3" <?= ($skip && in_array('status_change_-3', $skip)) ? 'checked' : '' ?>> <?= getMLText('timeline_skip_status_change_-3') ?><br />
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="enddate"></label>
		<div class="controls">
			<button type="submit" class="btn"><i class="icon-search"></i> <?php printMLText("action"); ?></button>
		</div>
	</div>
</form>
<?php
echo "</div>\n";
echo "</div>\n";

echo "<div class=\"span9\">\n";
$this->contentHeading(getMLText("timeline"));
if($data) {
	foreach($data as &$item) {
		switch($item['type']) {
		case 'add_version':
			$msg = getMLText('timeline_full_'.$item['type'], array('document'=>htmlspecialchars($item['document']->getName()), 'version'=> $item['version']));
			break;
		case 'add_file':
			$msg = getMLText('timeline_full_'.$item['type'], array('document'=>htmlspecialchars($item['document']->getName())));
			break;
		case 'status_change':
			$msg = getMLText('timeline_full_'.$item['type'], array('document'=>htmlspecialchars($item['document']->getName()), 'version'=> $item['version'], 'status'=> getOverallStatusText($item['status'])));
			break;
		default:
			$msg = '???';
		}
		$item['msg'] = $msg;
	}
	$this->printTimeline($data, 550, date('Y-m-d', $from), date('Y-m-d', $to+1), $skip);
}
echo "</div>\n";
echo "</div>\n";

$this->contentContainerEnd();
$this->htmlEndPage();
	} /* }}} */
}
?>
