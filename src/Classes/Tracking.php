<?php

namespace Aeros\Src\Classes;

final class Tracking
{
    /**
     * Returns the Google Tracking Parameters only
     * 
     * @param (string) query
     * @return (string)
     */
    public function google_url_tracking($query_string) : string
    {
        $authortized_utms = [
            'utm_id'       => '',
            'utm_source'   => '',
            'utm_medium'   => '',
            'utm_campaign' => '',
            'utm_term'     => '',
            'utm_content'  => ''
        ];

        if (strpos($query_string, '&')) {

            $query_params = explode('&', $query_string);

            foreach ($query_params as $param) {
                [$key, $value] = explode("=", $param);

                if (isset($authortized_utms[$key])) {
                    $authortized_utms[$key] = $value;
                }
            }
        }

        return json_encode($authortized_utms);
    }
}
