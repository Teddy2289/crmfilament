<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmCampaignWeeklyReport extends Model
{
    protected $table = 'crm_campaign_weekly_reports';

    protected $guarded = [];

    protected $casts = [
        'report_date' => 'date',
        'status_breakdown' => 'array',
        'status_trends' => 'array',
        'campaign_breakdown' => 'array',
        'comparison' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(CampagnePhoning::class, 'campaign_id');
    }
}
