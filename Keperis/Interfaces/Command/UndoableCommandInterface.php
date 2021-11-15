<?php

namespace Keperis\Interfaces\Command;

interface UndoableCommandInterface extends CommandInterface
{

    public function undo();
}
