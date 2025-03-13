<?php

namespace App\DataTransferObject;

class ServiceDTO
{
    public PropertiesDTO|null $properties;

    public function __construct(
        public string $id,
        array | null  $data
    ) {
        $this->properties = $data ? new PropertiesDTO(
            $data["cpu"] ?? 0,
            $data["memory"] * 1024 ?? 0
        ) : null;
    }

    public static function fromArray( array $data ): self
    {
        return new self(
            $data["id"],
            $data["targetProperties"] ?? null
        );
    }
}
