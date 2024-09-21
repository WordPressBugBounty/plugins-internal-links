<?php

namespace ILJ\Core\Links;

/**
 * Interface for transforming text in to linked content.
 */
interface Text_To_Link_Converter_Interface
{
    /**
     * Return the linked content.
     *
     * @param string $content
     * @return string
     */
    public function link_content(string $content): string;
}