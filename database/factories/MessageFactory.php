<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userIds = User::get()->pluck('id');

        return [
            'sender_id' => fake()->randomElement($userIds),
            'receiver_id' => fake()->randomElement($userIds),
            'content' => fake()->text(20),
        ];
    }
}
