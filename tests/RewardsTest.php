<?php
namespace FlipGive\Rewards\Tests;

use FlipGive\Rewards\Rewards;
use FlipGive\Rewards\InvalidPayloadException;
use FlipGive\Rewards\InvalidTokenException;

use Carbon\Carbon;

use PHPUnit\Framework\TestCase;

final class RewardsTest extends TestCase
{
    private const CLOUD_SHOP_ID = 'A2DE537C';
    private const SECRET = 'sk_61c394cf3346077b';

    private $campaignData;
    private $groupData;
    private $organizationData;
    private $payload;
    private $sut;
    private $userData;

    public function setUp(): void
    {
        $this->campaignData = $this->getCampaignData();
        $this->groupData = $this->getGroupData();
        $this->organizationData = $this->getOrganizationData();
        $this->userData = $this->getPersonData();
        $this->payload = [
            'user_data' => $this->userData,
            'campaign_data' => $this->campaignData,
        ];

        $this->sut = new Rewards(self::CLOUD_SHOP_ID, self::SECRET);
    }

    public function testTokenIsGeneratedAndCloudShopIdIsAppended(): void
    {
        $token = $this->sut->identifiedToken($this->payload);

        $this->assertIsString($token);
        $this->assertStringContainsString(self::CLOUD_SHOP_ID, $token, 'Generated token does not include the cloud shop id');
    }

    public function testExceptionIsThrownIfTokenDoesNotMatchCloudStoreId()
    {
        $this->expectException(InvalidTokenException::class);

        $token = str_replace(self::CLOUD_SHOP_ID, 'foobar', $this->sut->identifiedToken($this->payload));

        $this->sut->readToken($token);
    }

    public function testExceptionIsThrownForInvalidPayload()
    {
        $this->expectException(InvalidPayloadException::class);

        $this->sut->identifiedToken('foobar');
    }

    public function testErrorsInUserDataAreCaught()
    {
        $this->expectException(InvalidPayloadException::class);

        /**
         * In order to be able to test more than just the exception being thrown, we'll catch it, run the other
         * assertions and then rethrow the expected exception
         */
        try
        {
            $this->sut->identifiedToken(
                [
                    'user_data' => [],
                    'campaign_data' => null,
                ]
            );
        }
        catch (InvalidPayloadException $e)
        {
            $errors = $this->sut->getErrors();

            $this->assertNotEmpty($errors);
            $this->assertEquals(count($errors), 5);
            $this->assertEquals($errors[0]['payload'], 'At least must contain user_data or campaign_data.');
            $this->assertEquals($errors[1]['user_data'], 'id missing.');
            $this->assertEquals($errors[2]['user_data'], 'name missing.');
            $this->assertEquals($errors[3]['user_data'], 'email missing.');
            $this->assertEquals($errors[4]['user_data'], 'country must be one of: \'CAN, USA\'.');

            throw $e;
        }
    }

    public function testErrorsInCampaignDataAreCaught()
    {
        $this->expectException(InvalidPayloadException::class);

        try
        {
            $this->sut->identifiedToken(
                [
                    'user_data' => null,
                    'campaign_data' => [],
                ]
            );
        }
        catch (InvalidPayloadException $e)
        {
            $errors = $this->sut->getErrors();

            $this->assertNotEmpty($errors);
            $this->assertEquals(count($errors), 9);
            $this->assertEquals($errors[0]['payload'], 'At least must contain user_data or campaign_data.');
            $this->assertEquals($errors[1]['campaign_data'], 'id missing.');
            $this->assertEquals($errors[2]['campaign_data'], 'name missing.');
            $this->assertEquals($errors[3]['campaign_data'], 'category missing.');
            $this->assertEquals($errors[4]['campaign_data'], 'country must be one of: \'CAN, USA\'.');
            $this->assertEquals($errors[5]['campaign_admin_data'], 'id missing.');
            $this->assertEquals($errors[6]['campaign_admin_data'], 'name missing.');
            $this->assertEquals($errors[7]['campaign_admin_data'], 'email missing.');
            $this->assertEquals($errors[8]['campaign_admin_data'], 'country must be one of: \'CAN, USA\'.');

            throw $e;
        }
    }

    public function testTokenIsDecodedSuccessfully()
    {
        $token = $this->sut->identifiedToken($this->payload);
        $data = $this->sut->readToken($token);

        $this->assertEquals($data, $this->payload);
    }

    public function testPartnerTokenHasPartnerTokenType()
    {
        $token = $this->sut->getPartnerToken();
        $data = $this->sut->readToken($token);

        $expectedExpires = Carbon::now();
        $expectedExpires->addSeconds(Rewards::PARTNER_TOKEN_TTL);

        $this->assertEquals($data['type'], 'partner');
        $this->assertEquals($data['expires'], $expectedExpires->timestamp);
    }

    public function testTokenWithGroupDataIsSuccessfullyDecoded()
    {
        $this->payload['group_data'] = $this->groupData;

        $token = $this->sut->identifiedToken($this->payload);
        $data = $this->sut->readToken($token);

        $this->assertEquals($data, $this->payload);
    }

    public function testTokenWithGroupDataThrowsExceptionIfGroupDataIsInvalid()
    {
        $this->expectException(InvalidPayloadException::class);

        $this->payload['group_data'] = [];

        try
        {
            $this->sut->identifiedToken($this->payload);
        }
        catch (InvalidPayloadException $e)
        {
            $errors = $this->sut->getErrors();

            $this->assertNotEmpty($errors);
            $this->assertEquals(count($errors), 1);
            $this->assertEquals($errors[0]['group_data'], 'name missing.');

            throw $e;
        }
    }

    public function testTokenWithOrganizationDataIsSuccessfullyDecoded()
    {
        $this->payload['organization_data'] = $this->organizationData;

        $token = $this->sut->identifiedToken($this->payload);
        $data = $this->sut->readToken($token);

        $this->assertEquals($data, $this->payload);
    }

    public function testTokenWithOrganizationDataThrowsExceptionIfOrganizationDataIsInvalid()
    {
        $this->expectException(InvalidPayloadException::class);

        $this->payload['organization_data'] = [];

        try
        {
            $this->sut->identifiedToken($this->payload);
        }
        catch (InvalidPayloadException $e)
        {
            $errors = $this->sut->getErrors();

            throw $e;
        }
    }

    private function getCampaignData()
    {
        $faker = \Faker\Factory::create();

        return [
            'id' => rand(100000, 999999),
            'name' => $faker->word(),
            'category' => 'Running',
            'country' => 'CAN',
            'admin_data' => $this->getPersonData(),
        ];
    }

    private function getDivisionData()
    {
        $faker = \Faker\Factory::create();

        return [
            'id' => rand(100000, 999999),
            'name' => $faker->word() . ' chapter',
            'category' => 'Running',
            'country' => 'CAN',
            'admin_data' => $this->getPersonData(),
        ];
    }

    private function getGroupData()
    {
        $faker = \Faker\Factory::create();

        return [
            'name' => $faker->word(),
        ];
    }

    private function getOrganizationData()
    {
        $faker = \Faker\Factory::create();

        return [
            'id' => rand(100000, 999999),
            'name' => $faker->word(),
            'admin_data' => $this->getPersonData(),
        ];
    }

    private function getPersonData()
    {
        $faker = \Faker\Factory::create();

        return [
            'id' => rand(100000, 999999),
            'name' => $faker->name(),
            'email' => $faker->email(),
            'country' => 'CAN',
        ];
    }
}
