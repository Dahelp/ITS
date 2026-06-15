<?php

namespace app\widgets\mailbox;

class Mailbox
{
    public string $tpl;
    public array $data;

    public function __construct(string $tpl, array $data = [])
    {
        $this->tpl = $tpl;
        $this->data = $data;
        $this->run();
    }

    protected function run(): void
    {
        extract($this->data, EXTR_SKIP);
        require $this->tpl;
    }
}
