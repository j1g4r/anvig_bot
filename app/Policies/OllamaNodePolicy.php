<?php

namespace App\Policies;

use App\Models\OllamaNode;
use App\Models\User;

class OllamaNodePolicy
{
    public function view(User $user, OllamaNode $node): bool
    {
        return $user->id === $node->user_id;
    }

    public function update(User $user, OllamaNode $node): bool
    {
        return $user->id === $node->user_id;
    }

    public function delete(User $user, OllamaNode $node): bool
    {
        return $user->id === $node->user_id;
    }
}
