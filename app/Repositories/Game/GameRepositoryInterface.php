<?php

namespace App\Repositories\Game;
 
interface GameRepositoryInterface
{
    public function findById($id);
    public function delete($game);
} 