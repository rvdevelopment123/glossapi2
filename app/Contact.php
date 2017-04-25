<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\Collection;
class Contact extends Model
{
    //
    protected $fieldname1;


    public static function checkfield($fieldname){
        // return $fieldname;


        $fieldname1 = $fieldname;
        if (!Schema::hasColumn('contacts',$fieldname1))
        {
            Schema::table('contacts', function (Blueprint $table) use ($fieldname1)
            {
                $table->string($fieldname1)->nullable();
            });

            return "Created";
        }else{
            return "Already Exist";
        }
    }

    public function gotDuplicates($uniqueID)
    {
        $leadID = $this->attributes[$uniqueID];
        $query = Contact::where($uniqueID,"=",$leadID)->get();
        if(count($query) > 0){
            $contact = Contact::where($uniqueID,$leadID)->first();
        }else{
            $contact = new Contact;
        }
        $contact["attributes"] = $this->attributes;
        $contact->save();
        return count($query) > 0 ? true : false;
    }
}
