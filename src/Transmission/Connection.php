<?php

namespace Transmission;

class Connection {

    private
        $login = '',
        $password = '',
        $url = '',
        $ua = '',
        $action = 'get'
        ;

    /**
     * description
     *
     * @param void
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * description
     *
     * @param void
     * @return void
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    /**
     * description
     *
     * @param void
     * @return void
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * description
     *
     * @param void
     * @return void
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * description
     *
     * @param void
     * @return void
     */
    public function setUserAgent($ua)
    {
        $this->ua = $ua;

        return $this;
    }

    /**
     * description
     *
     * @param void
     * @return void
     */
    public function setPost($data)
    {
        $this->action   = 'post';
        $this->postData = $data;

        return $this;
    }

    /**
     * description
     *
     * @param void
     * @return void
     */
    public function login()
    {
        /**
         * login
         */
        $curlLogin = curl_init();

        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5, // seconds
            CURLOPT_MAXCONNECTS    => 5,
            CURLOPT_TIMEOUT        => 5, // seconds
            CURLOPT_USERAGENT      => $this->ua,
            CURLOPT_USERPWD        => sprintf('%s:%s', $this->login, $this->password),
        );

        curl_setopt_array($curlLogin, $options);
        curl_setopt($curlLogin, CURLOPT_URL, $this->url);

        $ret = curl_exec($curlLogin);
        $infos = curl_getinfo($curlLogin);

        switch($infos['http_code']) {
            case 0:
                throw new RPCException('Unable to connect to transmission');
                break;

            case 401:
                throw new RPCException('Wrong login/password');
                break;

            case 409:
                $sessionId = $this->extractSessionId($ret);
                break;

            default:
                throw new RPCException('Unhandled http code '.$infos['http_code']);
                break;
        }

        return $sessionId;
    }

    /**
     * description
     *
     * @param void
     * @return void
     */
    public function call()
    {
        $sessionId = $this->login();

        $curl = curl_init();

        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5, // seconds
            CURLOPT_MAXCONNECTS    => 5,
            CURLOPT_TIMEOUT        => 5, // seconds
            CURLOPT_USERAGENT      => $this->ua,
            CURLOPT_USERPWD        => sprintf('%s:%s', $this->login, $this->password),
            CURLOPT_HTTPHEADER     => array('Content-type: application/json', 'X-Transmission-Session-Id: '.$sessionId),
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $this->postData,
        );

        curl_setopt_array($curl, $options);
        curl_setopt($curl, CURLOPT_URL, $this->url);

        $ret = curl_exec($curl);
        $infos = curl_getinfo($curl);

        switch($infos['http_code']) {
            case 0:
                throw new RPCException('Unable to connect to transmission');
                break;

            case 401:
                throw new RPCException('Wrong login/password');
                break;

            case 409:
                throw new RPCException('Invalid X-Transmission-Session-Id. Please try again after calling GetSessionID()');
                break;

            case 200:
                break;

            default:
                throw new RPCException($ret);
                break;
        }

        return $ret;
    }

    private function extractSessionId($data)
    {
        $data = trim($data);

        $pattern = '#X-Transmission-Session-Id: ([a-zA-Z0-9]+)#';

        $nb = preg_match($pattern, $data, $matches);
        if (0 == $nb)
        {
            throw new TransmissionRPCException('Unable to find the X-Transmission-Session-Id');
        }

        return $matches[1];

    }

}
