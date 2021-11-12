<?php

namespace src\Interfaces\Command;

interface UndoableCommandInterface extends CommandInterface
{

    public function undo();
}
