<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RfidErrorPointResource extends JsonResource
{
    public function toArray($request): array
    {
        $base = parent::toArray($request);   // incluye todas las columnas de la tabla

        return array_merge($base, [
            'production_line' => $this->whenLoaded('productionLine'),
            'product_list'    => $this->whenLoaded('productList'),
            'operator'        => $this->whenLoaded('operator'),
            'operator_post'   => $this->whenLoaded('operatorPost'),
            'rfid_detail'     => $this->whenLoaded('rfidDetail'),
            'rfid_reading'    => $this->whenLoaded('rfidReading'),
            'rfid_color_name' => optional($this->rfidReading->rfidColor)->name,
            'note' => $this->note,
        ]);
    }
}
