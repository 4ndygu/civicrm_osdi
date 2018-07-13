<?php

/**
 * @file
 */

require_once __DIR__ . "/../../importers/PeopleStruct.php";
/**
 *
 */
class CRM_OSDIQueue_Tasks {

  public static $OSDICiviArray = array(
    "last_name" => "family_name",
    "first_name" => "given_name",
    "middle_name" => "additional_name",
  );

  /**
   *
   */
  public static function AddContact(CRM_Queue_TaskContext $context, $contact_wrapper) {
    // This expects a hal object that represents a page of contacts
    // where do u load an action?
    CRM_Core_Session::setStatus('executing add contact task', 'Queue task', 'success');

    $contactresource = unserialize($contact_wrapper);
    $contact = $contactresource->person;
    $group = $contactresource->groupid;
    $rule = $contactresource->rule;
    $apikey = $contactresource->apikey;

    // Load the mapping first
    // grab all fields.
    $url = $contactresource->endpoint;
    if (substr($apikey, 0, 4) != "OSDI") {
      $url = "actionnetwork";
    }
    $resultid = civicrm_api3('Mapping', 'get', array(
      'name' => "OSDIREMOTE_" . $url,
    ));

    $fieldresults = array();
    $fieldresults["values"] = array();

    if (isset($resultid["id"])) {
      $fieldresults = civicrm_api3('MappingField', 'get', array(
        'mapping_id' => $resultid["id"],
        'sequential' => 1,
        'options' => ['limit' => 0],
      ));
    }
    else {
      var_dump("mapping not found");
    }

    // Check if our ID is stored already.
    $contact_id = -1;
    if ($contact["custom_fields"] != NULL) {
      $hash = "CIVI_ID_actionnetwork"; 
      if (stripos($url, "actionnetwork.org") === FALSE) {
        $hash = "CIVI_ID_" . sha1($url); 
      }
      if (isset($contact["custom_fields"][$hash])) {
        if ($contact["custom_fields"][$hash] == sha1($url)) {
          $contact_id = $contact["custom_fields"][$hash];
        }
      }
    }

    // If not, match by dedupe rule.
    if ($contact_id == -1 or $contact_id == "") {
      $getParams = array();
      $getParams["sequential"] = 1;
      $getParams["contact_type"] = "Individual";

      $fieldsResponse = array();
      $fieldsResponse["values"] = array();

      if ($rule != NULL) {
        // Grab fields from rule and load up Contact.get query.
        $fieldsResponse = civicrm_api3('Rule', 'get', array(
          "sequential" => 1,
          "dedupe_rule_group_id" => $rule,
        ));

        foreach ($fieldsResponse["values"] as $field) {
          $actualField = $field["rule_field"];
          if ($actualField == "email") {
            $getParams[$actualField] = $contact["email_addresses"][0]["address"];
          }
          else {
            $getParams[$actualField] = $contact[CRM_OSDIQueue_Tasks::$OSDICiviArray[$actualField]];
          }
        }
      }

      if ($rule == NULL or $rule == "" or sizeof($fieldsResponse["values"]) == 0) {
        $getParams["email"] = $contact["email_addresses"][0]["address"];
      }

      $test = civicrm_api3('Contact', 'get', $getParams);

      if (sizeof($test["values"]) != 0) {
        $contact_id = $test["values"][0]["contact_id"];
      }
    }

    // This ultimately getVs passed to the api.
    $params = array();

    if (sizeof($fieldresults["values"]) == 0) {
      $params["first_name"] = $contact["given_name"];
      $params["last_name"] = $contact["family_name"];
      $params["email"] = $contact["email_addresses"][0]["address"];
      $params["display_name"] = $contact["family_name"];
      $params["contact_type"] = "Individual";
    }
    else {
      // Load into array.
      $fieldmapping = array();
      foreach ($fieldresults["values"] as $fieldresult) {
        if (!isset($fieldresult["name"])) {
          continue;
        }
        $fieldmapping[$fieldresult["name"]] = $fieldresult["value"];
      }

      // Call convert function.
      $params = convertOSDIContact($fieldmapping, $contact);
    }

    // Load the ID into your group
    // load the AN ID into custom_fieldss.
    $custom_fields = $contact["custom_fields"];
    if (isset($contact["identifiers"])) {
      $custom_fields["CIVI_ID_actionnetwork"] = $contact["identifiers"][0];
    }

    // Current key is sha1 of the /civicrm endpoint.
    $key = "CIVI_ID_actionnetwork";
    if (strpos($url, "actionnetwork.org") === FALSE) $key = "CIVI_ID_" . sha1($url);
    $tag = Civi::settings()->get('OSDIGROUPID');

    try {
      $need_update = FALSE;
      foreach ($custom_fields as $custom_field => $custom_value) {
        // Each custom field should be searchable.
        $results = civicrm_api3('CustomField', 'get', array(
          'custom_group_id' => $tag,
          'name' => $custom_field,
        ));

        // If custom field doesn't exist, create.
	if (sizeof($results["values"]) == 0) {
          $results = civicrm_api3('CustomField', 'create', array(
            'custom_group_id' => $tag,
            'label' => $custom_field,
            'data_type' => 'String',
            'html_type' => "Text",
          ));

          $OSDIvalue = "custom_fields|" . $custom_field;

          // Grab one side of the remote.
          $firstitem = civicrm_api3('Mapping', 'get', array(
            'name' => "osdi_contact",
          ));

          // Do same for remote fields.
          $seconditem = civicrm_api3('Mapping', 'get', array(
            'name' => "osdi_contact_remote",
          ));

	  // Shunt the forward direction.
          $result = civicrm_api3('MappingField', 'create', [
            'mapping_id' => $firstitem["id"],
            'name' => "custom_" . $results["id"],
            'value' => $OSDIvalue,
            'column_number' => 1,
          ]);

          // Shunt the backward direction.
          $result = civicrm_api3('MappingField', 'create', [
            'mapping_id' => $seconditem["id"],
            'name' => $OSDIvalue,
            'value' => "custom_" . $results["id"],
            'column_number' => 1,
          ]);

          // Call the update function.
          $need_update = TRUE;
        }

        // Search for field item here given the mapping
        // this ONLY works if field matching has failed earlier.
        $id = $results["id"];
        if (!isset($params["custom_" . $id])) {
          $params["custom_" . $id] = $custom_value;
        }
      }
      if ($need_update) {
        //$result = civicrm_api3('Mapping', 'Update', array()); 
      } 

      // Generate the field for this instance if it isn't generated.
      // DONT import it. only do that on export.
      $results = civicrm_api3('CustomField', 'get', array(
          'custom_group_id' => $tag,
          'label' => $key,
      ));

      if (sizeof($results["values"]) == 0) {
        $results = civicrm_api3('CustomField', 'create', array(
          'custom_group_id' => $tag,
          'label' => $key,
          'data_type' => 'String',
          'html_type' => "Text",
        ));
      }

      // for creating and/or updating the address later
      $addressparams = [
        'location_type_id' => "Home",
        'street_address' => isset($params["street_address"]) ? $params["street_address"] : "",
        'city' => isset($params["city"]) ? $params["city"] : "",
        'state_province_id' => isset($params["state_province_id"]) ? $params["state_province_id"] : "",
        'country_id' => isset($params["country_id"]) ? $params["country_id"] : "",
      ];

      // If contact exists, supply with id to update instead.
      $params["contact_type"] = "Individual";
      if ($contact_id == -1 or $contact_id == "") {
        $params["dupe_check"] = 1;
        $params["check_permission"] = 1;

        $result = civicrm_api3('Contact', 'create', $params);

        $addressparams["contact_id"] = $result["id"];

        // create address
        if (isset($params["country_id"]) || isset($params["city"])
          || isset($params["street_address"]) || isset($params["state_province_id"])) {
          $result = civicrm_api3('Address', 'create', $addressparams);
        }
      }
      else {
	      var_dump($params); var_dump($contact_id);
        $params["id"] = $contact_id;

        $result = civicrm_api3('Contact', 'create', $params);

        // check if this contact has an address
        $result = civicrm_api3('Address', 'get', [
          'sequential' => 1,
          'contact_id' => $params["id"],
        ]);


        $addressparams["contact_id"] = $params["id"];
        if (sizeof($result["values"]) != 0) {
          $addressparams["id"] = $result["values"][0]["id"];
        }

        // create address or update
        if (isset($params["country_id"]) || isset($params["city"])
          || isset($params["street_address"]) || isset($params["state_province_id"])) {
          $result = civicrm_api3('Address', 'create', $addressparams);
        }
      }

      // Add to group as well.
      if ($group != -1 and $group != NULL) {
        $result2 = civicrm_api3('GroupContact', 'create', array(
          'group_id' => $group,
          'contact_id' => $result["id"],
        ));
      }
    }
    catch (Exception $e) {
      //var_dump($e);
      return TRUE;
    }

    return TRUE;
  }

  /**
   *
   */
  public static function MergeContacts(CRM_Queue_TaskContext $context, $contact_wrapper) {
    // Deprecated.
    return TRUE;

    $contactresource = unserialize($contact_wrapper);
    $rule = $contactresource->rule;
    if ($rule == NULL or $rule == -1) {
      return TRUE;
    }

    // $dupes = CRM_Dedupe_Finder::dupes($rule);
    $mergestatus = CRM_Dedupe_Merger::batchMerge($rule);
    var_dump("MERGING");
    var_dump($mergestatus);

    return TRUE;
  }

}

/**
 *
 */
function getBranch($code, $resultkey, $contact, &$newcontact) {
  $pieces = explode('|', $code);
  $clone = $contact;

  $valid = TRUE;
  foreach ($pieces as $piece) {
    if (!isset($clone[$piece])) {
      $valid = FALSE;
      break;
    }
    $clone = $clone[$piece];
  }
  if ($valid) {
    $newcontact[$resultkey] = $clone;
  }
}

/**
 *
 */
function isJson($string) {
  json_decode($string);
  return (json_last_error() == JSON_ERROR_NONE);
}

/**
 *
 */
function convertOSDIContact($fieldmapping, $contact) {
  $newcontact = array();
  foreach ($fieldmapping as $key => $value) {
    if (isJson(stripcslashes($key)) and strpos($key, 'split') !== FALSE) {
      // This is a split item.
      $jsondecoded = json_decode($key, TRUE);
      $separator = $jsondecoded["split"];

      $finalvalue = array();
      $valid = TRUE;
      foreach ($jsondecoded as $jsonkey => $jsonvalue) {
        if ($jsonkey === "split") {
        }
        else {
          $smallpieces = explode('|', $jsonvalue);
          $clone = $contact;
          foreach ($smallpieces as $smallpiece) {
            if (!isset($clone[$smallpiece])) {
              $valid = FALSE;
              break;
            }
            $clone = $clone[$smallpiece];
          }

          if (!$valid) {
            break;
          }
          $finalvalue[] = $clone;
        }
      }

      if ($valid) {
        $finalvaluestring = join("-", $finalvalue);
        $newcontact[$value] = $finalvaluestring;
      }
    }
    elseif (strpos($key, '|') !== FALSE) {
      getBranch($key, $value, $contact, $newcontact);
    }
    else {
      if (!isset($contact[$key])) {
        // var_dump($key . " was not in the OSDI contact.");.
      }
      else {
        $newcontact[$value] = $contact[$key];
      }
    }
  }

  var_dump($newcontact);
  return $newcontact;
}
