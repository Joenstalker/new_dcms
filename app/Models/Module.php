<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    /** @use HasFactory<\Database\Factories\ModuleFactory> */

    public function up()
    {
        $this->create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->bigInteger('Plan_Id');
            $table->timestamps();
        });
    }
    
    use HasFactory;
}
