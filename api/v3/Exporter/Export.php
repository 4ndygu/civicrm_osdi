<?php
use CRM_Osdi_ExtensionUtil as E;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Hateoas\HateoasBuilder;

/**
 * Exporter.Export API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_exporter_Export_spec(&$spec) {
  $spec['object']['api.required'] = 1;
  $spec['apikey']['api.required'] = 0;
  $spec['sitekey']['api.required'] = 0;
  $spec['group']['api.required'] = 0;
}

/**
 * Exporter.Export API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_exporter_Export($params) {
    //TODO: convert to tertiary operators
    $offset = 0;
    if (!array_key_exists("page", $params)) {
        $params["page"] = 0;
    } else {
        $offset = $params["page"] * 25;
    }

    if (!array_key_exists("limit", $params)) {
        $limit = 25;
    } else {
        $limit = $params["limit"];
    }

    $filter = NULL;
    if (array_key_exists("filter", $params)) {
        $filter = $params["filter"];
    }

    // split the string
    // if first part isnt modified date set back to null
    $comparison = NULL;
    $date = NULL;
    $emailquery = NULL;
    if ($filter != NULL) {
        $pieces = preg_split("/\s+/", $filter);
        $validquery = ($pieces[0] == "modified_date" or $pieces[0] == "email_address") ? True : False;
        if ($pieces[0] == "modified_date" and sizeof($pieces) >= 3) {
            //set this to generate a reasonable datetime later
            if (sizeof($pieces) == 3) $pieces[3] = "";
            $comparison = $pieces[1];
            // check if reasonable
            $comparisons = array(
                "eq" => "=",
                "gt" => ">=",
                "lt" => "<="
            );

            if (!array_key_exists(trim($comparison), $comparisons)) {
                $comparison = NULL;
                $date = NULL;
            } else {
                $comparison = $comparisons[$comparison];
            }

            // TODO: validate this input
            $datepieces = array($pieces[2], $pieces[3]);
            $date = strtotime(substr(trim(join(" ", $datepieces)), 1, -1));
   
            // now convert this daet to the CIVI time zone
            if ($date != false) {
                $date = date('Y-m-d H:i:s', $date - 3600 * (int) $_SESSION["server_time_zone"]);
            } else {
                $date = "1980-01-01";
            }
        } else if ($pieces[0] == "email_address" and sizeof($pieces) >= 3) {
            if ($pieces[1] == "eq") {
                $emailquery = substr(trim($pieces[2]), 1, -1);
            }
        }
    }

    $result = NULL;
    if (strtolower($params["object"]) == "contact") {
        // unset params so future urls dont get fucked
        unset($params["object"]);
        unset($params["version"]);

        // dump the contacts

        $result = NULL;
        $singleuser = false;

        // get the list of optional parameters
        $tag = isset($_SESSION["OSDIGROUPID"]) ? $_SESSION["OSDIGROUPID"] : "osditags";
        $optionals= civicrm_api3('CustomField', 'get', array(
            'custom_group_id' => $tag
        ));

        $idnamemapping = array();
        $ingestcustomparams= ['modified_date'];
        if (sizeof($optionals["values"] != 0)) {
            foreach ($optionals["values"] as $custom_field) {
                $ingestcustomparams[] = "custom_" . $custom_field["id"];
                $idnamemapping["custom_" . $custom_field["id"]] = $custom_field["name"];
            }
        }

        if (array_key_exists("id", $params)) {
            $result = civicrm_api3('Contact', 'get', array(
                'contact_type' => "Individual",
                'sequential' => 1,
                'id' => $params["id"],
                'first_name' => array('IS NOT NULL' => 1),
                'last_name' => array('IS NOT NULL' => 1),
                'email' => array('IS NOT NULL' => 1),
                'options' => array("offset" => $offset, "limit" => $limit)
            ));
            $singleuser = true;
        } else {
            $queryparams = array( 
                'contact_type' => "Individual",
                'sequential' => 1,
                'options' => array("offset" => $offset, "limit" => $limit)
            );
            if ($emailquery != NULL) {
                $queryparams["email"] = $emailquery;
            }
            if ($comparison != NULL) {
                if ($comparison == "") { $queryparams["modified_date"] = $date; }
                else {
                    $queryparams["modified_date"] = array($comparison => $date);
                }
            }
            $result = civicrm_api3('Contact', 'get', $queryparams);
        }

        $apikey = (array_key_exists("apikey", $params) ? $params["apikey"] : "apikey");
        $sitekey = (array_key_exists("sitekey", $params) ? $params["sitekey"] : "sitekey");

        $response["properties"] = array();
        $response["properties"]["page"] = $offset / 25;
        $response["properties"]["per_page"] = $result["count"];

        //nextPage
        $config = CRM_Core_Config::singleton();
        $paramscopy = $params;
        $response["_links"]["self"] = CRM_Utils_System::url("civicrm/osdi/webhook", URLformat($paramscopy), TRUE, NULL, FALSE, TRUE);
        if (!$singleuser) {
            $paramscopy["page"]++;
            $nextarray = array();
            $nextarray["href"] = CRM_Utils_System::url("civicrm/osdi/webhook", URLformat($paramscopy), TRUE, NULL, FALSE, TRUE);
            $response["_links"]["next"] = $nextarray;
        }

        $response["_links"]["osdi:people"] = array();

        $response["_embedded"]["osdi:people"] = array();
        foreach ($result["values"] as $contact) {
            // pull custom params
            $optionals = civicrm_api3('Contact', 'get', array(
                'contact_type' => "Individual",
                'sequential' => 1,
                'id' => $contact["id"],
                'return' => $ingestcustomparams,
            ));

            // generate the link give nthe ID first
            $newparams = $params;
            $newparams["limit"] = 1;
            $newparams["id"] = $contact["id"];

            $contactURL = CRM_Utils_System::url("civicrm/osdi/webhook", URLformat($newparams), TRUE, NULL, FALSE, TRUE);

            $URLarray = array();
            $URLarray["href"] = $contactURL;
            $response["_links"]["osdi:people"][] = $URLarray;

            $url = CRM_Utils_System::url("civicrm");
            if ($apikey != "demokey") $url = "actionnetwork";
            $resultid = civicrm_api3('Mapping', 'get', array(
                'name' => "OSDIREMOTE_" . $url
            ));
            $fieldresults = civicrm_api3('MappingField', 'get', array(
                'mapping_id' => $resultid["id"],
                'sequential' => 1,
                'options' => ['limit' => 0],
            ));

            $newcontact = convertContactOSDI($contact, $fieldresults["values"]);
            $newcontact["modified_date"] = $optionals["values"][0]["modified_date"];
            $newcontact["custom_fields"] = array();
            foreach ($idnamemapping as $key => $value) {
                $newcontact["custom_fields"][$value] = $optionals["values"][0][$key];
            }

            $newcontact["_links"]["self"]["href"] = $contactURL;
            $response["_embedded"]["osdi:people"][] = $newcontact;
        }

        // if no users show up, don't provide a next option
        if (sizeof($response["_embedded"]["osdi:people"]) == 0) {
            unset($response["_links"]["next"]);
        }
    }

    return civicrm_api3_create_success($response, $params, 'Exporter', 'export');
}

function URLformat($params) {

    $finalstring = "";

    foreach ($params as $key => $value) {
        $finalstring = $finalstring . "&" . $key . "=" . $value;
    }
    return $finalstring;
}

function convertContactOSDI($contact, $fieldmapping) {
    $newcontact = array();

    if (sizeof($fieldmapping) == 0) {
        $newcontact["family_name"] = $contact["last_name"];
        $newcontact["given_name"] = $contact["first_name"];
        $newcontact["additional_name"] = $contact["middle_name"];
        $newcontact["honorific_prefix"] = $contact["prefix_id"];
        $newcontact["honorific_suffix"] = $contact["suffix_id"];
        $newcontact["gender_id"] = $contact["gender_id"];
        $newcontact["employer"] = $contact["current_employer"];

        $newcontact["email_addresses"][0]["address"] = $contact["email"];
        $newcontact["email_addresses"][0]["primary"] = True;

        $newcontact["postal_addresses"][0]["primary"] = True;
        $newcontact["postal_addresses"][0]["address_lines"][0] = $contact["street_address"];
        $newcontact["postal_addresses"][0]["locality"] = $contact["city"];
        $newcontact["postal_addresses"][0]["region"] = $contact["state_province_name"];
        $newcontact["postal_addresses"][0]["country"] = $contact["country"];
        $newcontact["postal_addresses"][0]["postal_code"] = $contact["postal_code"] . $contact["postal_code_suffix"];

        $newcontact["phone_numbers"][0] = array(
            "primary" => True,
            "number" => $contact["phone"]
        );
        $newcontact["phone_numbers"][0]["do_not_call"] = $contact["do_not_phone"];

        $tokenized_bday = explode("-", $contact["birth_date"]);
        if (sizeof($tokenized_bday) == 3) {
            $newcontact["birthdate"]["month"] = $tokenized_bday[2];
            $newcontact["birthdate"]["day"] = $tokenized_bday[1];
            $newcontact["birthdate"]["year"] = $tokenized_bday[0];
        }

        $newcontact["preferred_language"] = $contact["preferred_language"];

        $optionalparams = array("created_date", "identifiers");
        foreach ($optionalparams as $param) {
            if (isset($newcontact[$param])) {
                $newcontact[$param] = $contact[$param];
            }
        }
    } else {
        $newcontact = generateOSDIContact($fieldmapping, $newcontact);
    }

    // grab custom fields in group
    $resultfields = civicrm_api3('CustomField', 'get', array(
        'sequential' => 1,
        'custom_group_id' => $_SESSION["OSDIGROUPID"]
    ));

    // load all custom fields, add yourself too
    $customparams = array();
    $customfields = array();
    $customparams["id"] = $contact["contact_id"];
    $key = "ID_" . sha1(CRM_Utils_System::url("civicrm"));
    $selffound = False;

    foreach ($resultfields["values"] as $custom_field) {
        $customfields[] = "custom_" . $custom_field["id"]; 
        if ($custom_field["name"] == $key) $selffound = True;
    }
    $customparams["return"] = $customfields;

    $newcontact["custom_fields"] = array();
    if (sizeof($customparams["return"]) != 0) {
        $result = civicrm_api3('Contact', 'get', $customparams);

        if (sizeof($result["values"] != 0)) {
            foreach ($resultfields["values"] as $custom_field) {
                if (isset($result["values"][0])) {
                    $newcontact["custom_fields"][$custom_field["name"]] 
                        = $result["values"][0]["custom_" . $custom_field["id"]];
                }
            }
        }
    }

    // load yourself into the custom fields
    if (!$selffound) {
        $fieldresult = civicrm_api3('CustomField', 'create', array(
            'custom_group_id' => $_SESSION["OSDIGROUPID"],
        'label' => $key,
        'data_type' => 'String',
        'html_type' => "Text"
        ));
    }

    // add this to newcountacts
    $newcontact["custom_fields"][$key] = $result["id"];

    return $newcontact;
}


function isJson($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

function buildBranch($value, $code, &$newcontact) {
    $pieces = explode($code, '|');
    $numItems = count($pieces);
    $counter = 0;

    $branch = array();
    $originalbranch = &$branch;

    foreach ($pieces as $piece) {
        if(++$counter === $numItems) {
            $branch[$piece] = $value;
            break;
        } else {
            $branch[$piece] = array();
            $branch = $branch[$piece];
        }
    }

    $newcontact = array_merge($originalbranch, $newcontact);
}

function generateOSDIContact($fieldmapping, $contact) {
    $newcontact = array();
    foreach ($contact as $key => $value) {
        if (isset($fieldmapping[$key])) {
            // parse the language
            if (isJson($fieldmapping[$key])) {
                // this is a split item
                $jsondecoded = json_decode($fieldmapping[$key]);
                $separator = $jsondecoded["split"];

                $pieces = explode($value, $separator);

                $counter = 0;
                foreach ($pieces as $piece) {
                    buildBranch($piece, $jsondecoded[$counter], $newcontact);
                    $counter = $counter + 1;
                }
            } else if (strpos($fieldmapping[$key], '|') !== false) {
                buildBranch($value, $fieldmapping[$key], $newcontact);
            } else {
                $newcontact[$fieldmapping[$key]] = $value;
            }
        } else {
            var_dump("mapping not found!");
            $newcontact[$key] = $value;
        }
    }

    return $newcontact;
}