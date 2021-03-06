<?php
/**
 * Created by Nivanka Fonseka (nivanka@silverstripers.com).
 * User: nivankafonseka
 * Date: 7/24/15
 * Time: 11:28 AM
 * To change this template use File | Settings | File Templates.
 */

class SS_ReportExtension extends Extension {

    /**
     * @param Form $form
     * @return bool
     */
    public function updateEditForm(Form $form) {
		$fields = $form->Fields();
		if($gridField = $fields->fieldByName('Reports')){
			$gridField->setList($this->UpdatedReportList());
		}
		return false;
	}

    /**
     * @return ArrayList
     */
    public function UpdatedReportList() {
		$output = new ArrayList();
		foreach(SS_Report::get_reports() as $report) {
			if(!in_array(get_class($report), array('SideReport_BrokenFiles'))){
				if($report->canView()) {
					$output->push($report);
				}
			}

		}
		return $output;
	}

} 