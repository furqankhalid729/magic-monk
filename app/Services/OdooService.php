<?php

namespace App\Services;

use GuzzleHttp\Client;

class OdooService
{
    protected $url;
    protected $db;
    protected $username;
    protected $password;
    protected $client;
    protected $uid;

    public function __construct()
    {
        $this->url = env('ODOO_URL');
        $this->db = env('ODOO_DB');
        $this->username = env('ODOO_USERNAME');
        $this->password = env('ODOO_PASSWORD');

        $this->client = new Client(['base_uri' => $this->url]);

        $this->authenticate();
    }

    private function authenticate()
    {
        $response = $this->client->post('/xmlrpc/2/common', [
            'json' => [
                'method' => 'authenticate',
                'params' => [$this->db, $this->username, $this->password, []]
            ]
        ]);
        
        $this->uid = json_decode($response->getBody(), true)['result'];
    }

    public function createInvoice($customerId, $orderData)
    {
        // Prepare the invoice payload
        $invoice = [
            'partner_id' => $customerId,
            'move_type' => 'out_invoice',
            'invoice_line_ids' => [[
                0, 0, [
                    'name' => $orderData['product_name'],
                    'quantity' => $orderData['quantity'],
                    'price_unit' => $orderData['price'],
                ]
            ]]
        ];

        // Send to Odoo
        $response = $this->client->post('/xmlrpc/2/object', [
            'json' => [
                'method' => 'execute_kw',
                'params' => [
                    $this->db,
                    $this->uid,
                    $this->password,
                    'account.move',     // Odoo invoice model
                    'create',           // Odoo method
                    [$invoice]
                ]
            ]
        ]);

        return json_decode($response->getBody(), true)['result'] ?? null;
    }
}
