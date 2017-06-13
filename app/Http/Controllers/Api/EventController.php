<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\EventRequest;
use App\Exceptions\Api\UnknowException;
use App\Http\Controllers\AbstractController;
use App\Repositories\Contracts\EventInterface;
use App\Repositories\Contracts\QualityInterface;
use App\Repositories\Contracts\CampaignInterface;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateEventRequest;
use App\Traits\Common\UploadableTrait;

class EventController extends ApiController
{
    protected $eventRepository;
    protected $qualityRepository;
    protected $campaignRepository;

    public function __construct(
        EventInterface $eventRepository,
        QualityInterface $qualityRepository,
        CampaignInterface $campaignRepository
    ) {
        parent::__construct();
        $this->qualityRepository = $qualityRepository;
        $this->campaignRepository = $campaignRepository;
        $this->eventRepository = $eventRepository;
    }

    public function create(EventRequest $request)
    {
        $input['data_event'] = $request->only('campaign_id', 'title', 'description', 'longitude', 'latitude');
        $input['data_event']['user_id'] = $this->user->id;
        $input['other'] = $request->only('settings', 'files');
        $input['donations'] = $this->qualityRepository->getOrCreate($request->get('donations'));
        $campaign = $this->campaignRepository->find($input['data_event']['campaign_id']);

        return $this->doAction(function () use ($input, $campaign) {
            if (!$this->user->can('createEvent', $campaign)) {
                throw new UnknowException('Have error when create event');
            }

            $this->compacts['event'] = $this->eventRepository->create($input);
        });
    }      

    public function update(EventRequest $request, $id)
    {
        $data = $request->only('title', 'description', 'longitude',
            'latitude', 'mediaAdds','mediaDels', 'goalDels', 'goalUpdates', 'goalAdds');
        $event = $this->eventRepository->getEventExist($id);

        if ($this->user->cannot('manage', $event)) {
            throw new UnknowException('Permission error: User can not edit this event.');
        }

        return $this->doAction(function () use ($event, $data) {
            if (!empty($data['goalAdds'])) {
                $data['goalAdd'] = $this->qualityRepository->getOrCreate($data['goalAdds']);
            }
            
            if (!empty($data['goalUpdates'])) {
                $data['goalUpdate'] = $this->qualityRepository->getOrCreate($data['goalUpdates']);
            }

            $data = array_except($data, ['goalAdds']);
            $this->compacts['event'] = $this->eventRepository->update($event, $data);
        });
    }

    public function  updateSetting(Request $request, $id)
    {
        $event = $this->eventRepository->getEventExist($id);

        return $this->doAction(function () use ($event, $request) {
            $this->compacts['event'] = $this->eventRepository->updateSettings($event, $request->setting);
        });
    }
}
