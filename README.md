# FlipgiveSDK

## Rewards

Rewards is [FlipGive's](https://www.flipgive.com) drop-in cashback program. If you would like to know more please contact us at partners@flipgive.com.

### Links of Interest

- [FlipGive](https://www.flipgive.com)
- [API Documentation](https://docs.flipgive.com)

### Installation

To begin using `\FlipGive\Rewards\Rewards`, you should have obtained an `ID` and `Secret` pair from FlipGive, store these securely so that they are accessible in your application (env variables, etc). If you haven't received credentials, please contact us at partners@flipgive.com.

Add the flipgive/rewards package to your composer.json:

```bash
composer require --save flipgive/rewards
```

After you have installed the package include the code below to initialize the SDK:

```php
use FlipGive\Rewards\Rewards;

$rewards = new Rewards($rewards_id, $rewards_secret);
```

The SDK is now ready to use.

### Usage

The main purpose of `\FlipGive\Rewards\Rewards` is to generate Tokens to gain access to FlipGive's Shop Cloud API. There are 6 methods on the package's public API.

#### __construct
This method is used to initialize the SDK, as described on the setup section of this document. It takes 2 arguments, the `rewards_id` and the `rewards_secret`.

#### readToken
This method is used to decode a token that has been generated with your credentials. It takes a single string as an argument and, if able to decode the token, it will return a hash.

```php
$token = "eyJhbGciOiJkaXIiLCJlbmMiOiJBMTI4R0NNIn0..demoToken.g8PZPWb1KDFcAkTsufZq0w@A2DE537C";

$rewards->readToken($token);
=> [ 'user_data' => [ 'id' => 1, 'name' => 'Emmett Brown', 'email' => 'ebrown@time.ca', 'country' => 'USA' ] ]
```

#### identifiedToken
This method is used to generate a token that will identify a user or campaign. It accepts a **Payload Hash** as an argument and it returns an encrypted token.

```php
$payload = [
  'user_data' => $user_data,
  'campaign_data' => $campaign_data,
  'group_data' => $group_data,
  'organization_data' => $organization_data
];

$rewards->identifiedToken($payload);
=> "eyJhbGciOiJkaXIiLCJlbmMiOiJBMTI4R0NNIn0..demoToken.g8PZPWb1KDFcAkTsufZq0w@A2DE537C"
```

The variable in this example uses other variables, ($user_data, $campaign_data, etc.). let's look at each one of them:


- `user_data`: **required** when `campaign_data` is not present in the payload, otherwise optional. It represents the user using the Shop, and  contains the following information:
  - `id`: **required**. A string representing the user's ID in your system.
  - `email`: **required**. A string with the user's email.
  - `name`: **required**. A string with the user's name.
  - `country`: **required**. A string with the ISO code of the user's country, which must be 'CAN' or 'USA' at this time.
  - `city`: *optional*. A string with the user's city.
  - `state`: *optional*. A string with the user's state. It must be a 2 letter code. You can see a list of values [here](https://github.com/BetterTheWorld/FlipGiveSDK_Ruby/blob/main/states.yml).
  - `postal_code`: A string with the user's postal code. It must match Regex `/\d{5}/` for the USA or `/[a-zA-Z]\d[a-zA-Z]\d[a-zA-Z]\d/` for Canada.
  - `latitude`: *optional*. A float with the user's latitude in decimal degree format. Without accompanying `:longitude`, latitude will be ignored.
  - `longitude`: *optional*. A float with the user's longitude in decimal degree format. Without accompanying `:latitude`, longitude will be ignored.
  - `image_url`: *optional*. A string containing the URL for the user's avatar.

Optional fields of invalid formats will not be validated but will be ignored.

  ```php
  $user_data = [
    'id' => 19850703,
    'name' => 'Emmett Brown',
    'email' => 'ebrown@time.com',
    'country' => 'USA'
  ];
  ```
Optional fields of invalid formats will not be validated but will be ignored.

- `campaign_data`: Required when user_data is not present in the payload, otherwise optional. It represents the fundraising campaign and contains the following information:

  - `id`: **required** A string representing the user's ID in your system.
  - `name`: **required** A string  with the campaign's email.
  - `category`: **required** A string  with the campaign's category. We will try to match it with one of our existing categories, or assign a default. You can see a list of our categories [here](https://github.com/BetterTheWorld/FlipGiveSDK_Ruby/blob/main/categories.txt).
  - `country`: **required** A string  with the ISO code of the campaign's country, which must be 'CAD' or 'USA' at this time.
  - `admin_data`: **required** The user information for the campaign's admin. It must contain the same information as `user_data`
  - `city`: *optional*. A string with the campaign's city.
  - `state`: *optional*. A string with the campaign's state. It must be a 2 letter code. You can see a list [here](https://github.com/BetterTheWorld/FlipGiveSDK_Ruby/blob/main/states.yml).
  - `postal_code`: A string with the campaign's postal code. It must match Regex `/\d{5}/` for the USA or `/[a-zA-Z]\d[a-zA-Z]\d[a-zA-Z]\d/` for Canada.
  - `latitude`: *optional*. A float with the campaign's latitude in decimal degree format.
  - `longitude`: *optional*. A float with the campaign's longitude in decimal degree format.
  - `image_url`: *optional*. A string containing the URL for the campaign's image, if any.

Optional fields of invalid formats will not be validated but will be ignored.

  ```php
  $campaign_data = [
    'id' => 19551105,
    'name' => 'The Time Travelers',
    'category' => 'Events & Trips',
    'country' => 'USA',
    'admin_data' => $user_data
  ];
  ```

- `group_data`: *Always optional*. Groups are aggregators for users within a campaign. For example, a group can be a Player on a sport's team and the users would be the people supporting them.
  - `name`: **required**. A string with the group's name.
  - `player_number`: *optional*. A sport's player number on the team.

  ```php
  $group_data = [
    'name' => 'Marty McFly'
  ];
  ```

- `organization_data`: Always optional. Organizations are used to group campaigns. As an example: A School (organization) has many Grades (campaigns), with Students (groups) and Parents (users) shopping to support their student.
  - `id`: **required**. A string with the organization's ID.
  - `name`: **required**. A string with the organization's name.
  - `organization_admin`: **required**. The user information for the organization's admin. It must contain the same information as `$user_data`

  ```php
  $organization_data = [
    'id' => 980,
    'name' => 'Back to the Future',
    'admin_data' => $user_data
  ];
  ```

- `utm_data`:  Always optional. UTM data will be saved when a campaign and/or user is created.
  - `utm_medium`: A string representing utm_medium.
  - `utm_campaign`: A string representing utm_campaign.
  - `utm_term`: A string representing utm_term.
  - `utm_content`: A string representing utm_content.
  - `utm_channel`: A string representing utm_channel.

  ```php
  $utm_data = [
    'utm_medium' => 'Universal Pictures',
    'utm_campaign' => 'Movie',
    'utm_term' => 'Time, Travel',
    'utm_content' => 'Image',
    'utm_channel' => 'Time'
  ];
  ```

#### validIdentified
This method is used to validate a payload, without attempting to generate a token. It returns a Boolean. The same rules for `identifiedToken` apply here as well.

```php
$payload = [
  'user_data' => $user_data
];

$rewards->validIdentified($payload);
=> true
```

#### getPartnerToken
This method is used to generate a token that can **only** be used by the Shop Cloud partner (that's you) to access reports and other API endpoints. It is only valid for an hour.

```php
$rewards->getPartnerToken();
=> "eyJhbGciOiJkaXIiLCJlbmMiOiJBMTI4R0NNIn0..demoToken.h9QXQEn2LFGVSlTdiGXW1e@A2DE537C"
```

#### getErrors
Validation errors that occur while attempting to generate a token can be retrieved here.

```php
$user_data['country'] = 'ENG';

$payload = [
  'user_data' => $user_data,
];

$rewards->validIdentified($payload);

# InvalidPayloadException

$rewards->getErrors();

=> [['user_data' => "Country must be one of: 'CAN, USA'." ]]
```

### Support

For developer support please open an [issue](https://github.com/BetterTheWorld/FlipGiveSDK_PHP/issues) on this repository.

### Contributing

Bug reports and pull requests are welcome on GitHub at [https://github.com/BetterTheWorld/FlipGiveSDK_PHP](https://github.com/BetterTheWorld/FlipGiveSDK_PHP).

## License

This library is distributed under the
[Apache License, version 2.0](http://www.apache.org/licenses/LICENSE-2.0.html)

```no-highlight
copyright 2023. FlipGive, inc. all rights reserved.

licensed under the apache license, version 2.0 (the "license");
you may not use this file except in compliance with the license.
you may obtain a copy of the license at

    http://www.apache.org/licenses/license-2.0

unless required by applicable law or agreed to in writing, software
distributed under the license is distributed on an "as is" basis,
without warranties or conditions of any kind, either express or implied.
see the license for the specific language governing permissions and
limitations under the license.
```
