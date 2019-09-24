<?php

namespace App\Imports;

use App\Models\Tenant\Item;
use App\Models\Tenant\Series;
use App\Models\Tenant\Document;
use App\Models\Tenant\Warehouse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Modules\Import\Models\ImportDocument;

class CustomDocumentsImport implements ToCollection
{
    use Importable;

    protected $data;

    public function collection(Collection $rows)
    {

        
            $total = count($rows);
            $registered = 0;
            unset($rows[0]);
            $i = 1;
            // dd(count($rows));
            $quantity_rows = count($rows);
            
            if($quantity_rows){
                $import_document = ImportDocument::create(
                    ['user_id'=> auth()->user()->id]
                );
            }

            for($i; $i <= $quantity_rows; $i++)
            { 

                $row = $rows[$i];
                $row_items = [];

                //unidad de medida 
                $unit_type = 'NIU';
                $unit_price = $row[32];
                $unit_value = round($unit_price/1.18,2);
                $quantity = 1;
                $total_igv_item = $unit_price - $unit_value;
                $total_valor_item = $unit_value * $quantity;
                $total_item = $unit_price * $quantity;

                $row_items [] = [
                    "codigo_interno" => $row[3],
                    "descripcion" => rtrim($row[35]),
                    "codigo_producto_sunat" => "",
                    "unidad_de_medida" => $unit_type,
                    "cantidad" => $quantity, //todo
                    "valor_unitario" => $unit_value,
                    "codigo_tipo_precio" => "01",
                    "precio_unitario" => $unit_price,
                    "codigo_tipo_afectacion_igv" => "10",
                    "total_base_igv" => $unit_value * $quantity, //todo
                    "porcentaje_igv" => "18",
                    "total_igv" => $total_igv_item,
                    "total_impuestos" => $total_igv_item,
                    "total_valor_item" => $total_valor_item,
                    "total_item" => $total_item
                ];

                $row_items [] = [
                    "codigo_interno" => "ENVIO",
                    "descripcion" => "ENVIO",
                    "codigo_producto_sunat" => "",
                    "unidad_de_medida" => $unit_type,
                    "cantidad" => 1,
                    "valor_unitario" => round($row[33]/1.18,2),
                    "codigo_tipo_precio" => "01",
                    "precio_unitario" => $row[33],
                    "codigo_tipo_afectacion_igv" => "10",
                    "total_base_igv" => round($row[33]/1.18,2),
                    "porcentaje_igv" => "18",
                    "total_igv" => $row[33] - round($row[33]/1.18,2),
                    "total_impuestos" => $row[33] - round($row[33]/1.18,2),
                    "total_valor_item" => round($row[33]/1.18,2),
                    "total_item" => $row[33]
                ];

                for ($j=$i+1; $j <= $quantity_rows; $j++) { 
               
                    // dd((string)$row[6], (string)$rows[$j][6], (string)$rows[$j+1][6]);
                    
                    if((string)$row[6] == (string)$rows[$j][6]){
                        
                        $row_temp = $rows[$j];
                        
                        $unit_price = $row_temp[32];
                        $unit_value = round($unit_price/1.18,2);
                        $quantity = 1;
                        $total_igv_item = $unit_price - $unit_value;
                        $total_valor_item = $unit_value * $quantity;
                        $total_item = $unit_price * $quantity;

                        $row_items [] = [
                            "codigo_interno" => $row_temp[3],
                            "descripcion" => rtrim($row_temp[35]),
                            "codigo_producto_sunat" => "",
                            "unidad_de_medida" => $unit_type,
                            "cantidad" => $quantity, //todo
                            "valor_unitario" => $unit_value,
                            "codigo_tipo_precio" => "01",
                            "precio_unitario" => $unit_price,
                            "codigo_tipo_afectacion_igv" => "10",
                            "total_base_igv" => $unit_value * $quantity, //todo
                            "porcentaje_igv" => "18",
                            "total_igv" => $total_igv_item,
                            "total_impuestos" => $total_igv_item,
                            "total_valor_item" => $total_valor_item,
                            "total_item" => $total_item
                        ];

                        $row_items [] = [
                            "codigo_interno" => "ENVIO",
                            "descripcion" => "ENVIO",
                            "codigo_producto_sunat" => "",
                            "unidad_de_medida" => $unit_type,
                            "cantidad" => 1,
                            "valor_unitario" => round($row_temp[33]/1.18,2),
                            "codigo_tipo_precio" => "01",
                            "precio_unitario" => $row_temp[33],
                            "codigo_tipo_afectacion_igv" => "10",
                            "total_base_igv" => round($row_temp[33]/1.18,2),
                            "porcentaje_igv" => "18",
                            "total_igv" => $row_temp[33] - round($row_temp[33]/1.18,2),
                            "total_impuestos" => $row_temp[33] - round($row_temp[33]/1.18,2),
                            "total_valor_item" => round($row_temp[33]/1.18,2),
                            "total_item" => $row_temp[33]
                        ];

                        unset($rows[$j]);
                        $i = $j;
                        // dd($j);
                    }
                }
                
                
                $document_type_operation = '0101';
                $correlativo = "#";

                if($row[9] == 'Yes'){
                    $document_type = '01';
                } elseif($row[9] == 'No'){
                    $document_type = '03';
                } 

                $establishment_id = auth()->user()->establishment_id;
                $record_serie = Series::where([['document_type_id',$document_type],['establishment_id',$establishment_id]])->first();

                if($record_serie){
                    $serie = $record_serie->number;
                }else{
                    throw new Exception("No tiene serie registrada");                        
                }
                 

                $create_date = $row[4];
                $date_create = Carbon::parse($create_date)->format('Y-m-d');
                $time_create = Carbon::parse($create_date)->format('H:i:s');

                $currency = $row[8];

                //cliente
                $co_number = rtrim($row[11]);
                if ($co_number > 0) {
                    if (strlen($co_number) == 11) {
                        $client_document_type = '6';
                        $company_number = $co_number;
                    } elseif (strlen($co_number) == 8) {
                        $client_document_type = '1';
                        $company_number = $co_number;
                    }
                } 

                $company_address = $row[13];
                $company_name = $row[10];


                //totales
                $acum_total_item = 0;

                foreach ($row_items as $item) {
                    // dd($item);
                    $acum_total_item += $item['total_item'];
                }

                $mtototal = $acum_total_item;
                $mtosubtotal = round($mtototal/1.18,2);
                $mtoimpuesto = $mtototal - $mtosubtotal;

                

                // $unit_price = $row[32];
                // $unit_value = round($unit_price/1.18,2);
                // $quantity = 1;
                // $total_igv_item = $unit_price - $unit_value;
                // $total_valor_item = $unit_value * $quantity;
                // $total_item = $unit_price * $quantity;

                //genero json y envio a api para no hacer insert 
                
                $json = array(
                    "serie_documento" => $serie,
                    "numero_documento" => $correlativo,
                    "fecha_de_emision" => $date_create,
                    "hora_de_emision" => $time_create,
                    "codigo_tipo_operacion" => $document_type_operation,
                    "codigo_tipo_documento" => $document_type,
                    "codigo_tipo_moneda" => $currency,
                    "fecha_de_vencimiento" => $date_create,
                    "numero_orden_de_compra" => "-",
                    "totales" => [
                        "total_exportacion" => 0.00,
                        "total_operaciones_gravadas" => $mtosubtotal,
                        "total_operaciones_inafectas" => 0.00,
                        "total_operaciones_exoneradas" => 0.00,
                        "total_operaciones_gratuitas" => 0.00,
                        "total_igv" => $mtoimpuesto,
                        "total_impuestos" => $mtoimpuesto,
                        "total_valor" => $mtosubtotal,
                        "total_venta" => $mtototal
                    ],
                    "datos_del_emisor" => [
                        "codigo_del_domicilio_fiscal" => "0000"
                    ],
                    "datos_del_cliente_o_receptor" => [
                        "codigo_tipo_documento_identidad" => $client_document_type,
                        "numero_documento" => $company_number,
                        "apellidos_y_nombres_o_razon_social" => rtrim($company_name),
                        "codigo_pais" => "PE",
                        "ubigeo" => "010101",
                        "direccion" => rtrim($company_address),
                        "correo_electronico" => "",
                        "telefono" => ""
                    ],
                    "items" => $row_items
                    // "items" => [
                    //     [
                    //         "codigo_interno" => $row[3],
                    //         "descripcion" => rtrim($row[35]),
                    //         "codigo_producto_sunat" => "",
                    //         "unidad_de_medida" => $unit_type,
                    //         "cantidad" => $quantity, //todo
                    //         "valor_unitario" => $unit_value,
                    //         "codigo_tipo_precio" => "01",
                    //         "precio_unitario" => $unit_price,
                    //         "codigo_tipo_afectacion_igv" => "10",
                    //         "total_base_igv" => $unit_value * $quantity, //todo
                    //         "porcentaje_igv" => "18",
                    //         "total_igv" => $total_igv_item,
                    //         "total_impuestos" => $total_igv_item,
                    //         "total_valor_item" => $total_valor_item,
                    //         "total_item" => $total_item
                    //     ]
                    // ]
                );

                //envio
                // if ($row[33]) {
                //     $new_item = [
                //             "codigo_interno" => "ENVIO",
                //             "descripcion" => "ENVIO",
                //             "codigo_producto_sunat" => "",
                //             "unidad_de_medida" => $unit_type,
                //             "cantidad" => 1,
                //             "valor_unitario" => round($row[33]/1.18,2),
                //             "codigo_tipo_precio" => "01",
                //             "precio_unitario" => $row[33],
                //             "codigo_tipo_afectacion_igv" => "10",
                //             "total_base_igv" => round($row[33]/1.18,2),
                //             "porcentaje_igv" => "18",
                //             "total_igv" => $row[33] - round($row[33]/1.18,2),
                //             "total_impuestos" => $row[33] - round($row[33]/1.18,2),
                //             "total_valor_item" => round($row[33]/1.18,2),
                //             "total_item" => $row[33]
                //         ];
                //     array_push($json["items"], $new_item);
                // }

                $url = url('/api/documents');
                $token = auth()->user()->api_token;

                // dd($json);

                try {

                    $client = new \GuzzleHttp\Client();

                    $res = $client->post($url, [
                        'headers' => [
                            'Content-Type' => 'Application/json',
                            'Authorization' => 'Bearer '.$token
                        ],
                        'json' => $json
                    ]);

                    $response = json_decode($res->getBody()->getContents(), true);
// dd($res);
                    Document::where('external_id', $response['data']['external_id'])->update(['import_document_id' => $import_document->id]);
                    // dd($response['data']['external_id']);

                } catch (Exception $e) {
                    throw new Exception("Error Processing Request", 1);
                    
                }

                $registered += 1;
            }
            $this->data = compact('total', 'registered');

    }

    public function getData()
    {
        return $this->data;
    }
}
