<?php

namespace Spatie\LaravelTypeScriptTransformer\Exceptions;

use Exception;

class DuplicateActionNameException extends Exception
{
    /** @param array<string, array<string>> $duplicates */
    public function __construct(
        public readonly array $duplicates,
    ) {
        $message = "Duplicate action names detected:\n";

        foreach ($duplicates as $resolvedName => $fqcns) {
            $message .= "  '{$resolvedName}' resolves from:\n";
            foreach ($fqcns as $fqcn) {
                $message .= "    - {$fqcn}\n";
            }
        }

        parent::__construct($message);
    }
}
