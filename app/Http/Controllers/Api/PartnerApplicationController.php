<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PartnerApplicationRequest;
use App\Models\HotelApplication;
use App\Models\HotelDocument;

class PartnerApplicationController extends Controller
{
    public function store(PartnerApplicationRequest $request)
    {
        // منع التقديم المزدوج
        if (auth()->user()->hotelApplication()->exists()) {
            return response()->json([
                'message' => 'You have already submitted an application.'
            ], 422);
        }

        $application = HotelApplication::create([
            'user_id'          => auth()->id(),
            'hotel_name'       => $request->hotel_name,
            'property_address' => $request->property_address,
            'property_type'    => $request->property_type,
            'legal_name'       => $request->legal_name,
            'job_title'        => $request->job_title,
            'contact_email'    => $request->contact_email,
            'contact_phone'    => $request->contact_phone,
        ]);

        $docTypes = [
            'business_license',
            'vat_certificate',
            'insurance_certificate',
            'representative_id',
        ];

        foreach ($docTypes as $type) {
            $file = $request->file($type);
$path = $file->store("hotel-applications/{$application->id}/{$type}", 'public');
            $application->documents()->create([
                'type'          => $type,
                'original_name' => $file->getClientOriginalName(),
                'disk_path'     => $path,
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
            ]);
        }

        return response()->json([
            'message' => 'Application submitted successfully.'
        ], 201);
    }
}