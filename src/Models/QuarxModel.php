<?php

namespace Yab\Quarx\Models;

use Yab\Quarx\Models\Archive;
use Yab\Quarx\Models\Translation;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class QuarxModel extends Model
{
    /**
     * Model contructuor
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (config('quarx.db-prefix', '')) {
            $this->table = config('quarx.db-prefix', '').$this->table;
        }
    }

    /**
     * After the item is saved to the database
     *
     * @param  Object $payload
     * @return void
     */
    public function afterSaved($payload)
    {
        unset($payload->attributes['created_at']);
        unset($payload->attributes['updated_at']);
        unset($payload->original['created_at']);
        unset($payload->original['updated_at']);

        if ($payload->attributes != $payload->original) {
            Archive::create([
                'token'         => md5(time()),
                'entity_id'     => $payload->attributes['id'],
                'entity_type'   => get_class($payload),
                'entity_data'   => json_encode($payload->attributes),
            ]);
            Log::info(get_class($payload).' #'.$payload->attributes['id'].' was archived');
        }
    }

    /**
     * When the item is being deleted
     *
     * @param  Object $payload
     * @return void
     */
    public function beingDeleted($payload)
    {
        $type = get_class($payload);
        $id = $payload->id;

        Translation::where('entity_id', $id)->where('entity_type', $type)->delete();
        Archive::where('entity_id', $id)->where('entity_type', $type)->delete();

        Archive::where('entity_type', 'Yab\Quarx\Models\Translation')
            ->where('entity_data', 'LIKE', '%"entity_id":'.$id.'%')
            ->where('entity_data', 'LIKE', '%"entity_type":"'.$type.'"%')
            ->delete();
    }
}
