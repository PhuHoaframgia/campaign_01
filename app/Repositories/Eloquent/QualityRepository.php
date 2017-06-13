<?php

namespace App\Repositories\Eloquent;

use App\Models\Quality;
use App\Repositories\Contracts\QualityInterface;

class QualityRepository extends BaseRepository implements QualityInterface
{
    public function model()
    {
        return Quality::class;
    }

    public function getOrCreate($data)
    {
        if (!$data) {
            return null;
        }

        foreach ($data as $key => $value) {
            $nameQuality = $this->where('name', $value['quality'])->first();
            
            if (!$nameQuality) {
                //file name of quality don't exitst then create new quality and donationType
                $quality = $this->create([
                    'name' => $value['quality'],
                ]);
                $donationType = $quality->donationTypes()->create([
                    'name' => $value['type'],
                ]);
                $result[$key]['donation_type_id'] = $donationType->id;
            } else {
                
                $donationType = $nameQuality->donationTypes()->get(['id', 'name'])->pluck('name', 'id')->toArray();
                $donationTypeId = array_search($value['type'], $donationType);

                if ($donationTypeId) {
                    //file name of quality is exitst then get id of record in donationType.
                    $result[$key]['donation_type_id'] = $donationTypeId;
                } else {
                    //quality is exitst va donation_type don't exitst then create create new in donationType
                    $newId = $nameQuality->donationTypes()->create([
                        'name' => $value['type'],
                    ])->id;
                    $result[$key]['donation_type_id'] = $newId;
                }
            }

            $result[$key]['goal'] = $value['goal'];
        }

        return $result;
    }
}
