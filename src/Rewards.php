<?php
namespace FlipGive\Rewards;

use Carbon\Carbon;

class Rewards
{
    const COUNTRIES = ['CAN', 'USA'];
    const PARTNER_TOKEN_TTL = 3600; // seconds

    private $cloudShopId;
    private $errors;
    private $jweHelper;

    public function __construct(string $cloudShopId, string $secret)
    {
        $this->cloudShopId = $cloudShopId;
        $this->errors = [];
        $this->jweHelper = new JweHelper(str_replace('sk_', '', $secret));
    }

    public function readToken($token)
    {
        list($encryptedString, $shopId) = explode('@', $token);

        if ($shopId !== $this->cloudShopId)
        {
            throw new InvalidTokenException();
        }

        return json_decode($this->jweHelper->decrypt($encryptedString), true);
    }

    public function identifiedToken($payload)
    {
        if (!$this->validIdentified($payload))
        {
            throw new InvalidPayloadException();
        }

        $token = $this->jweHelper->encrypt($payload);

        return implode('@', [$token, $this->cloudShopId]);
    }

    public function getPartnerToken()
    {
        $expires = Carbon::now();
        $expires->addSeconds(self::PARTNER_TOKEN_TTL);

        $payload = [
            'type' => 'partner',
            'expires' => $expires->timestamp,
        ];

        $token = $this->jweHelper->encrypt($payload);

        return implode('@', [$token, $this->cloudShopId]);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function validIdentified($payload)
    {
        $this->errors = [];

        $this->validatePayload($payload);

        if (isset($payload['user_data']))
        {
            $this->validatePersonData('user_data', $payload['user_data']);
        }

        if (isset($payload['campaign_data']))
        {
            $this->validateCampaignData('campaign_data', $payload['campaign_data']);
        }

        if (isset($payload['group_data']))
        {
            $this->validateGroupData($payload['group_data']);
        }

        if (isset($payload['organization_data']))
        {
            $this->validateOrganizationData('organization_data', $payload['organization_data']);
        }

        return count($this->errors) === 0;
    }

    private function validatePayload($payload)
    {
        $this->validateFormat($payload);
        $this->validateMinimumData($payload);
    }

    private function validatePersonData($key, $data)
    {
        $this->validatePresence($key, $data, 'id');
        $this->validatePresence($key, $data, 'name');
        $this->validatePresence($key, $data, 'email');
        $this->validateInclusion($key, self::COUNTRIES, $data, 'country');
    }

    private function validateCampaignData($key, $data)
    {
        $this->validatePresence($key, $data, 'id');
        $this->validatePresence($key, $data, 'name');
        $this->validatePresence($key, $data, 'category');
        $this->validateInclusion($key, self::COUNTRIES, $data, 'country');
        $this->validatePersonData('campaign_admin_data', isset($data['admin_data']) ? $data['admin_data'] : []);
    }

    private function validateGroupData($data)
    {
        if (empty($data['name']))
        {
            $this->errors[] = ['group_data' => 'name missing.'];
        }
    }

    private function validateOrganizationData($key, $data)
    {
        $this->validatePresence($key, $data, 'id');
        $this->validatePresence($key, $data, 'name');
        $this->validatePersonData('organization_admin_data', isset($data['admin_data']) ? $data['admin_data'] : []);
    }

    private function validatePresence($dataKey, $data, $key)
    {
        if (empty($data[$key]))
        {
            $this->errors[] = [$dataKey => sprintf('%s missing.', $key)];
        }
    }

    private function validateInclusion($dataKey, $group, $data, $key)
    {
        if (!isset($data[$key]) || array_search($data[$key], $group) === false)
        {
            $this->errors[] = [$dataKey => sprintf('%s must be one of: \'%s\'.', $key, implode(', ', $group))];
        }
    }

    private function validateFormat($payload)
    {
        if (!is_array($payload))
        {
            $this->errors[] = ['payload' => 'Payload must be an array.'];
        }
    }

    private function validateMinimumData($payload)
    {
        if (empty($payload) || (empty($payload['user_data']) && empty($payload['campaign_data'])))
        {
            $this->errors[] = ['payload' => 'At least must contain user_data or campaign_data.'];
        }
    }
}
