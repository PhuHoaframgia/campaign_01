<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campaign;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CreateCampaign extends TestCase
{
    use DatabaseTransactions;
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCreateCampaignSuccess()
    {
        $faker = \Faker\Factory::create();
        $user = factory(User::class)->create();
        $this->actingAs($user, 'api');
        $response = $this->json('POST', route('campaign.create'), [
            'title' => $faker->name,
            'description' => $faker->paragraph(),
            'hashtag' => $faker->unique()->name,
            'longitude' => $faker->longitude($min = -180, $max = 180),
            'latitude' => $faker->latitude($min = -90, $max = 90),
            'tags' => [
                'php',
                'laravel',
                'vue.js',
                'mysql',
            ],
            'settings' => [
                [
                    'key' => 1,
                    'value' => 1,
                ],
                [
                    'key' => 2,
                    'value' => 5,
                ],
                [
                    'key' => 3,
                    'value' => null,
                ],
            ],
            'media' => UploadedFile::fake()->image('avatar.jpg', 600, 600),
        ], [
            'HTTP_Authorization' => 'Bearer ' . $user->createToken('myToken')->accessToken,
        ]);

        $response->assertStatus(CODE_OK)->assertExactJson([
            'campaign' => true,
            'http_status' => [
                'code' => CODE_OK,
                'status' => true,
            ],
        ]);
    }

    public function testCreateCampaignWithNullTagThenSuccess()
    {
        $faker = \Faker\Factory::create();
        $user = factory(User::class)->create();
        $this->actingAs($user, 'api');
        $response = $this->json('POST', route('campaign.create'), [
            'title' => $faker->name,
            'description' => $faker->paragraph(),
            'hashtag' => $faker->unique()->name,
            'longitude' => $faker->longitude($min = -180, $max = 180),
            'latitude' => $faker->latitude($min = -90, $max = 90),
            'tags' => [],
            'settings' => [
                [
                    'key' => 1,
                    'value' => 0,
                ],
                [
                    'key' => 2,
                    'value' => 10,
                ],
            ],
            'media' => UploadedFile::fake()->image('avatar1.jpg', 600, 600),
        ], [
            'HTTP_Authorization' => 'Bearer ' . $user->createToken('myToken')->accessToken,
        ]);

        $response->assertStatus(CODE_OK)->assertExactJson([
            'campaign' => true,
            'http_status' => [
                'code' => CODE_OK,
                'status' => true,
            ],
        ]);
    }

    public function testCreateCampaignWithNullSettingThenSuccess()
    {
        $faker = \Faker\Factory::create();
        $user = factory(User::class)->create();
        $this->actingAs($user, 'api');
        $response = $this->json('POST', route('campaign.create'), [
            'title' => $faker->name,
            'description' => $faker->paragraph(),
            'hashtag' => $faker->unique()->name,
            'longitude' => $faker->longitude($min = -180, $max = 180),
            'latitude' => $faker->latitude($min = -90, $max = 90),
            'tags' => [
                'mysql',
                'demo',
            ],
            'settings' => [],
            'media' => UploadedFile::fake()->image('avatar2.jpg', 600, 600),
        ], [
            'HTTP_Authorization' => 'Bearer ' . $user->createToken('myToken')->accessToken,
        ]);

        $response->assertStatus(CODE_OK)->assertExactJson([
            'campaign' => true,
            'http_status' => [
                'code' => CODE_OK,
                'status' => true,
            ],
        ]);
    }

    public function testCreateCampaignWithDuplicateHashTagThenFail()
    {
        $faker = \Faker\Factory::create();
        $user = factory(User::class)->create();
        $this->actingAs($user, 'api');
        factory(Campaign::class)->create(['hashtag' => 'duplicate']);
        $response = $this->json('POST', route('campaign.create'), [
            'title' => $faker->name,
            'description' => $faker->paragraph(),
            'hashtag' => 'duplicate',
            'longitude' => $faker->longitude($min = -180, $max = 180),
            'latitude' => $faker->latitude($min = -90, $max = 90),
            'tags' => [
                'mysql',
                'demo',
            ],
            'settings' => [
                'key' => 1,
                'value' => 0,
            ],
            'media' => UploadedFile::fake()->image('avatar2.jpg', 600, 600),
        ], [
            'HTTP_Authorization' => 'Bearer ' . $user->createToken('myToken')->accessToken,
        ]);
    
        $response->assertStatus(VALIDATOR_ERROR)->assertJson([
            'code' => VALIDATOR_ERROR,
            'status' => false,
            'messages' => [
                trans('validation.unique', ['attribute' => 'hashtag']),
            ],
        ]);
    }

    public function testCreateCampaignWithFiledsNullThenFail()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user, 'api');
        $response = $this->json('POST', route('campaign.create'), [
            'title' => null,
            'description' => null,
            'hashtag' => null,
            'longitude' => null,
            'latitude' => null,
            'tags' => null,
            'settings' => null,
        ], [
            'HTTP_Authorization' => 'Bearer ' . $user->createToken('myToken')->accessToken,
        ]);

        $response->assertStatus(VALIDATOR_ERROR)->assertJson([
            'code' => VALIDATOR_ERROR,
            'status' => false,
            'messages' => [
                trans('validation.required', ['attribute' => 'title']),
                trans('validation.required', ['attribute' => 'description']),
                trans('validation.required', ['attribute' => 'hashtag']),
                trans('validation.required', ['attribute' => 'media']),
            ],
        ]);
    }

    public function testCreateCampaignWithInvalitSettingThenFail()
    {
        $faker = \Faker\Factory::create();
        $user = factory(User::class)->create();
        $this->actingAs($user, 'api');
        $response = $this->json('POST', route('campaign.create'), [
            'title' => $faker->name,
            'description' => $faker->paragraph(),
            'hashtag' => $faker->unique()->name,
            'longitude' => $faker->longitude($min = -180, $max = 180),
            'latitude' => $faker->latitude($min = -90, $max = 90),
            'tags' => [],
            'settings' => [
                [
                    'key' => 3,
                    'value' => null,
                ],
                'key' => 1,
                'value' => 1,
            ],
            'media' => UploadedFile::fake()->image('avatar2.jpg', 600, 600),
        ], [
            'HTTP_Authorization' => 'Bearer ' . $user->createToken('myToken')->accessToken,
        ]);

        $response->assertStatus(INTERNAL_SERVER_ERROR)->assertExactJson([
            'http_status' => [
                'status' => false,
                'code' => INTERNAL_SERVER_ERROR,
                'message' => 'Invalit format settings array',
        ]]);
    }

    public function testCreateCampaignWithInvalitLongtitudeLatitudeThenFail()
    {
        $faker = \Faker\Factory::create();
        $user = factory(User::class)->create();
        $this->actingAs($user, 'api');
        $response = $this->json('POST', route('campaign.create'), [
            'title' => $faker->name,
            'description' => $faker->paragraph(),
            'hashtag' => $faker->unique()->name,
            'longitude' => 181,
            'latitude' => -91,
            'tags' => [],
            'settings' => [
                [
                    'key' => 3,
                    'value' => null,
                ],
                'key' => 1,
                'value' => 1,
            ],
            'media' => UploadedFile::fake()->image('avatar2.jpg', 600, 600),
        ], [
            'HTTP_Authorization' => 'Bearer ' . $user->createToken('myToken')->accessToken,
        ]);

        $response->assertStatus(VALIDATOR_ERROR)->assertExactJson([
            'code' => VALIDATOR_ERROR,
            'status' => false,
            'messages' => [
                trans('validation.max.numeric', ['attribute' => 'longitude', 'max' => 180]),
                trans('validation.min.numeric', ['attribute' => 'latitude', 'min' => -90]),
            ],    
        ]);
    }
}
