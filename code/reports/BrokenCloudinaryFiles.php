<?php
/**
 * Created by Nivanka Fonseka (nivanka@silverstripers.com).
 * User: nivankafonseka
 * Date: 7/24/15
 * Time: 11:25 AM
 * To change this template use File | Settings | File Templates.
 */

class BrokenCloudinaryFiles extends SS_Report {

	public function title() {
		return 'Pages with broken files';
	}
	public function group() {
		return 'Broken links reports';
	}
	public function sourceRecords($params = null) {
		// Get class names for page types that are not virtual pages or redirector pages
		$classes = array_diff(ClassInfo::subclassesFor('SiteTree'), ClassInfo::subclassesFor('VirtualPage'), ClassInfo::subclassesFor('RedirectorPage'));
		$classNames = "'".join("','", $classes)."'";

		if (isset($_REQUEST['OnLive'])) $ret = Versioned::get_by_stage('SiteTree', 'Live', "\"ClassName\" IN ($classNames) AND \"HasBrokenFile\" = 1");
		else $ret = DataObject::get('SiteTree', "\"ClassName\" IN ($classNames) AND \"HasBrokenFile\" = 1");
		return $ret;
	}
	public function columns() {
		return array(
			"Title" => array(
				"title" => "Title", // todo: use NestedTitle(2)
				"link" => true,
			),
		);
	}

	public function getParameterFields() {
		return new FieldList(
			new CheckboxField('OnLive', _t('SideReport.ParameterLiveCheckbox', 'Check live site'))
		);
	}

} 