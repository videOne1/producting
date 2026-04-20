<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestResource extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $testResource)
    {
        return "Message from TestResource.php. Clicked item: {$testResource}";
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $testResource)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $testResource)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $testResource)
    {
        //
    }
}
