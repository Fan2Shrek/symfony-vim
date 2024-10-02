<?php

namespace App;

final class Buffer
{
    public function __construct(private string $content = '')
    {
        $this->content = $content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function addContent(string $content): void
    {
        $this->content .= $content;
    }

    public function clear(): void
    {
        $this->content = '';
    }

    public function flush(): string
    {
        $content = $this->content;
        $this->clear();

        return $content;
    }
}
