<?php

/**
 *
 * This class contains a function getCaseActivity() that overrides core CRM_Activity_Page_AJAX::getCaseActivity(),
 * in order to fill activity priority, to be rendered by case activities datatable.
 *
 */
class CRM_Caseactivitylisting_Page_AJAX {

  public static function getCaseActivity() {
    // Should those params be passed through the validateParams method?
    $caseID = CRM_Utils_Type::validate($_GET['caseID'], 'Integer');
    $contactID = CRM_Utils_Type::validate($_GET['cid'], 'Integer');
    $userID = CRM_Utils_Type::validate($_GET['userID'], 'Integer');
    $context = CRM_Utils_Type::validate(CRM_Utils_Array::value('context', $_GET), 'String');

    $optionalParameters = [
      'source_contact_id' => 'Integer',
      'status_id' => 'Integer',
      'activity_deleted' => 'Boolean',
      'activity_type_id' => 'Integer',
      // "Date" validation fails because it expects only numbers with no hyphens
      'activity_date_low' => 'Alphanumeric',
      'activity_date_high' => 'Alphanumeric',
    ];

    $params = CRM_Core_Page_AJAX::defaultSortAndPagerParams();
    $params += CRM_Core_Page_AJAX::validateParams([], $optionalParameters);

    // get the activities related to given case
    $activities = CRM_Case_BAO_Case::getCaseActivity($caseID, $params, $contactID, $context, $userID);

    // fill activity priority for datatables to render.
    $activityIds = [];
    foreach ($activities['data'] as $row) {
      if (!empty($row['DT_RowId'])) {
        $activityIds[] = $row['DT_RowId'];
      }
    }
    $priorities = [];
    if (!empty($activityIds)) {
      $options = CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'priority_id');
      $sql = "SELECT id, priority_id FROM civicrm_activity WHERE id IN (" . implode(',', $activityIds) . ")";
      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        $priorities[$dao->id] = CRM_Utils_Array::value($dao->priority_id, $options, ts('Normal'));
      }
      foreach ($activities['data'] as &$row) {
        if (!empty($row['DT_RowId']) && !empty($priorities[$row['DT_RowId']])) {
          $row['priority'] = $priorities[$row['DT_RowId']];
        }
      }
    }

    CRM_Utils_JSON::output($activities);
  }

}
