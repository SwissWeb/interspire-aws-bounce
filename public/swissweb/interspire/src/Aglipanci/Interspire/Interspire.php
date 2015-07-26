<?php namespace Aglipanci\Interspire;


class Interspire
{

    /**
     * @var string
     */
    private $api_url;

    /**
     * @var string
     */
    private $api_user;

    /**
     * @var string
     */
    private $api_token;

    public function __construct()
    {
        $this->api_url = config('interspire.url');
        $this->api_user = config('interspire.api_user');
        $this->api_token = config('interspire.api_token');
    }

    /**
     * POST data to API
     * @param $xml
     * @return string
     */
    private function postData($xml)
    {
        $ch = curl_init($this->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $result = curl_exec($ch);

        if ($result === false || is_null($result) || empty($result))
            return null;

        $xml_doc = simplexml_load_string($result);

        /** @noinspection PhpUndefinedFieldInspection */
        if ($xml_doc->status == 'SUCCESS')
            return 'OK';

        /** @noinspection PhpUndefinedFieldInspection */
        return $xml_doc->errormessage->__toString();
    }

    /**
     * Add subscriber to list
     * @param $name
     * @param $surname
     * @param $email
     * @param $list_id
     * @return string
     */
    public function addSubscriberToList($name, $surname, $email, $list_id)
    {

        $xml = '<xmlrequest>
		<username>' . $this->api_user . '</username>
		<usertoken>' . $this->api_token . '</usertoken>
		<requesttype>subscribers</requesttype>
		<requestmethod>AddSubscriberToList</requestmethod>
		<details>
		<emailaddress>' . $email . '</emailaddress>
		<mailinglist>' . $list_id . '</mailinglist>
		<format>html</format>
		<confirmed>yes</confirmed>
		<customfields>
		<item>
		<fieldid>2</fieldid>
		<value>' . $name . '</value>
		</item>
		<item>
		<fieldid>3</fieldid>
		<value>' . $surname . '</value>
		</item>
		</customfields>
		</details> 
		</xmlrequest>
		';

        return $this->postData($xml);
    }

    /**
     * Delete a subscriber
     * @param $email
     * @param int|string $list_id
     * @return string
     */
    public function addBannedSubscriber($email, $list_id = 'global')
    {

        $xml = '<xmlrequest>
		<username>' . $this->api_user . '</username>
		<usertoken>' . $this->api_token . '</usertoken>
		<requesttype>subscribers</requesttype>
		<requestmethod>AddBannedSubscriber</requestmethod>
		<details>
		<emailaddress>' . $email . '</emailaddress>
		<list>' . $list_id . '</list>
		</details>
		</xmlrequest>';

        return $this->postData($xml);
    }

    /**
     * Delete a subscriber
     * @param $email
     * @param int $list_id
     * @return string
     */
    public function deleteSubscriber($email, $list_id = 1)
    {

        $xml = '<xmlrequest>
		<username>' . $this->api_user . '</username>
		<usertoken>' . $this->api_token . '</usertoken>
		<requesttype>subscribers</requesttype>
		<requestmethod>DeleteSubscriber</requestmethod>
		<details>
		<emailaddress>' . $email . '</emailaddress>
		<list>' . $list_id . '</list>
		</details>
		</xmlrequest>';

        return $this->postData($xml);
    }

    /**
     * Check if user is on list
     * @param $email
     * @param $list_id
     * @return string
     */
    public function isSubscriberOnList($email, $list_id)
    {
        $xml = '<xmlrequest>
		<username>' . $this->api_user . '</username>
		<usertoken>' . $this->api_token . '</usertoken>
		<requesttype>subscribers</requesttype>
		<requestmethod>IsSubscriberOnList</requestmethod>
		<details>
		<Email>' . $email . '</Email>
		<List>' . $list_id . '</List>
		</details>
		</xmlrequest>';

        return $this->postData($xml);
    }

}
