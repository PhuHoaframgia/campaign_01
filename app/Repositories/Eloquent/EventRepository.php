<?php

namespace App\Repositories\Eloquent;

use Exception;
use App\Models\Event;
use App\Models\Media;
use App\Traits\Common\UploadableTrait;
use App\Exceptions\Api\UnknowException;
use App\Exceptions\Api\NotFoundException;
use App\Repositories\Contracts\EventInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EventRepository extends BaseRepository implements EventInterface
{
    use UploadableTrait;

    public function model()
    {
        return Event::class;
    }

    public function delete($eventIds)
    {
        if (!$eventIds) {
            $events = $this->model->whereIn('id', $eventIds);
            $events->goals()->delete();
            $events->action()->delete();

            return $events->delete();
        }
    }

    public function create($data)
    {
        $donation = $data['donations'];
        $dataSettings = $this->createDataSettings($data['other']['settings']);
        $dataMedias = $this->createDataMedias($data['other']['files']);
        $event = $this->model->create($data['data_event']);

        if (!$event) {
            throw new NotFoundException('Have error when create model');
        }

        $createSettings = $event->settings()->createMany($dataSettings);

        if (!$createSettings) {
            throw new NotFoundException('Have error when create model');
        }

        $createMedias = $event->media()->createMany($dataMedias);

        if (!$createMedias) {
            throw new NotFoundException('Have error when create model');
        }

        if ($donation) {
            $goals = $event->goals()->createMany($donation);

            if (!$goals) {
                throw new NotFoundException('Have error when create model');
            }
        }

        return true;
    }

    private function createDataSettings($data)
    {
        if (!is_array($data)) {
            throw new UnknowException('Data type is incorrect');
        }

        foreach ($data as $key => $value) {
            $result[] = [
                'key' => $key,
                'value' => $value,
            ];
        }

        return $result;
    }

    private function createDataMedias($data)
    {
        if (!is_array($data)) {
            throw new UnknowException('Data type is incorrect');
        }

        foreach ($data as $value) {
            $urlFile = $this->uploadFile($value, 'event');

            if (!$urlFile) {
                throw new UnknowException('Upload file fail');
            }

            $result[] = [
                'type' => Media::IMAGE,
                'url_file' => $urlFile,
            ];
        }

        return $result;
    }

    public function update($event, $inputs)
    { 
        $goals = $event->goals()->get();

        if (isset($inputs['goalAdd'])) { 
            if ($goals) {
                foreach ($inputs['goalAdd'] as $key => $goal) {
                    $checkGoalExist = $event->goals
                        ->where('donation_type_id', $goal['donation_type_id'])
                        ->where('goal', $goal['goal'])
                        ->isEmpty();
                    if ($checkGoalExist) {
                        $event->goals()->create($goal);
                    }
                }
             } else {
                $event->goals()->createMany($inputs['goalAdd']);
            }
        }

        if (isset($inputs['goalUpdate']) && $goals) {
            foreach ($inputs['goalUpdate'] as $key => $value) {
                $goal = $event->goals()->find($key);
                
                if (!$goal) {
                    throw new UnknowException('Error: Goal is not found.');
                }

                $goal->update($value);
            }
        }

        if (!empty($inputs['mediaDels'])) { 
            foreach ($inputs['mediaDels'] as $mediaId) {
                $media = $event->media()->find($mediaId);

                if(!$file) {
                   throw new UnknowException('Error: Image is not found');
                }
                
                $media->destroyFile($file->url_file, 'image');
                $media->delete();
            }
        }

        if (!empty($inputs['mediaAdds'])) { 
            foreach ($inputs['mediaAdds'] as $mediaId) {
                $urlFile = $this->uploadFile($mediaId, 'event');
                $event->media()->create([
                    'url_file' => $urlFile,
                    'type' => Media::IMAGE,
                ]);
            }
        }

        if (!empty($inputs['goalDels'])) { 
            $event->goals()->delete($inputs['goalDels']);
        }
        
        $inputs = array_except($inputs , ['mediaAdds', 'mediaDels']);
        parent::update($event->id, $inputs);

        return true;
    }
    

    public function updateSettings($event, $listSetting) 
    {
        $settings = $event->settings->pluck('id')->toArray();

        if (array_keys($listSetting) != $settings) {
            throw new UnknowException('Error: Key does not match.');
        }

        foreach ($listSetting as $key => $setting) {
            $event->settings->find($key)->update([
                'value' => $setting,
            ]);
        }

        return true;
    }

    public function getEventExist($id)
    {
        $event = $this->find($id);
        
        if (!$event) {
            throw new UnknowException('Error: Event is not found.');
        } 

        return $event;
    }
}
