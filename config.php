<?php

return array(
    "last_name" => "family_name",
    "first_name" => "given_name",
    "middle_name" => "additional_name",
    "prefix_id" => "honorific_prefix",
    "suffix_id" => "honorific_suffix",
    "current_employer" => "employer",
    "email" => "email_addresses|0|address",
    "street_address" => "postal_addresses|0|address_lines|0",
    "city" => "postal_addresses|0|locality",
    "state_province_name" => "postal_addresses|0|region",
    "country" => "postal_addresses|0|country",
    "postal_code" => "postal_addresses|0|postal_code",
    "postal_code_suffix" => "postal_addresses|0|postal_code+",
    "phone" => "phone_numbers|0|number",
    "do_not_phone" => "phone_numbers|0|do_not_call",
    "birth_date" => json_encode(array(
        "split" => "-",
        0 => "birthdate|year",
        1 => "birthdate|day",
        2 => "birthdate|month",
    ))
);
