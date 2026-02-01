<?php 

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class OrderController extends BaseController
{
    public function index()
    {
        $this->requireAdmin();
    }

    public function show($id)
    {
        $this->requireAdmin();
    }
}
