<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 *
 * @package   local_sigaaintegration
 * @copyright 2024, Igor Ferreira Cemim
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_sigaaintegration;

use core\http_client;
use moodle_exception;

class sigaa_api_client  {

    private $apibaseurl;

    private $apiclientid;

    private $apiclientsecret;

    private $client;

    private $accesstoken;

    private const ENROLLMENTS_URL = "/api/v1/sig/sigaa/matriculados";

    private const OAUTH_TOKEN_URL = "/oauth/token";

    public function __construct($apibaseurl, $apiclientid, $apiclientsecret) {
        $this->apibaseurl = $apibaseurl;
        $this->apiclientid = $apiclientid;
        $this->apiclientsecret = $apiclientsecret;
    }

    public function get_enrollments($year, $period) : array {
        $response = $this->get_http_client()->get($this->apibaseurl . self::ENROLLMENTS_URL, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$this->get_access_token()}",
            ],
            'query' => [
                'ano' => $year,
                'periodo' => $period,
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new moodle_exception(
                sprintf(
                    "ERRO: Falha ao buscar matrículas na API do SIGAA. statusCode: %s, responseBody: %s",
                    $response->getStatusCode(),
                    $response->getBody()->getContents()
                )
            );
        }

        return $this->decode($response->getBody()->getContents());
    }

    protected function get_access_token() : string {
        if ($this->accesstoken !== null) {
            return $this->accesstoken;
        }

        $response = $this->get_http_client()->post($this->apibaseurl . self::OAUTH_TOKEN_URL, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->apiclientid,
                'client_secret' => $this->apiclientsecret,
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new moodle_exception(
                sprintf(
                    "ERRO: Falha ao buscar access_token na API do SIGAA. statusCode: %s, responseBody: %s",
                    $response->getStatusCode(),
                    $response->getBody()->getContents()
                )
            );
        }

        $this->accesstoken = $this->decode($response->getBody()->getContents())['access_token'];

        return $this->accesstoken;
    }

    protected function get_http_client() : http_client {
        if ($this->client === null) {
            $this->client = new http_client();
        }
        return $this->client;
    }

    private function decode($content) : array {
        return json_decode($content, true);
    }
}
