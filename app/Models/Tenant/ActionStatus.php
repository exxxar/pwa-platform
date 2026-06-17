<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActionStatus extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tenant_user_id',
        'tenant_id',
        'max_attempts',
        'current_attempts',
        'completed_at',
        'data',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'tenant_user_id' => 'integer',
        'tenant_id' => 'integer',
        'completed_at' => 'datetime:Y-m-d',
        'data' => 'array',
    ];


    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }


    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class);
    }



  /*  public static function prepare($botUser, $bot, $slug, $maxAttempts = 1){


        $action = ActionStatus::query()
            ->where("user_id", $botUser->user_id)
            ->where("tenant_id", $bot->id)
            ->where("slug_id", $slug->id)
            ->first();

        if (is_null($action))
            $action = ActionStatus::query()
                ->create([
                    'tenant_id' => $bot->id,
                    'slug_id' => $slug->id,
                    'max_attempts' => $maxAttempts,
                    'current_attempts' => 0,
                    'tenant_user_id' => $botUser->id
                ]);

        $action->max_attempts = $maxAttempts;

        if (is_null($action->data)) {
            $action->current_attempts = 0;
            $action->save();
        }

        return $action;
    }*/
}
