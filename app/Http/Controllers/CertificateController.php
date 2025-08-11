<?php
 
namespace App\Http\Controllers;
 
use App\Models\Certificate;
use Illuminate\Http\Request;
 
class CertificateController extends Controller
{
    
    public function index()
    {
        return Certificate::latest()->paginate(20);
    }

    public function show(Certificate $certificate)
    {
        return $certificate;
    }
}
 
 
 