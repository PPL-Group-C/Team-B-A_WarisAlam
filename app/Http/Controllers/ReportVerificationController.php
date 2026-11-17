<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVerificationRequest;
use App\Models\DamageReport;
use Illuminate\Http\Request;

class ReportVerificationController extends Controller
{
    /** Confirm or dispute a report. Voting again replaces the earlier vote. */
    public function store(StoreVerificationRequest $request, DamageReport $report)
    {
        abort_if($report->status === 'draft', 404);
        abort_if($report->user_id === $request->user()->id, 403, 'Anda tidak dapat memverifikasi laporan sendiri.');

        $verdict = $request->validated('verdict');

        $report->verifications()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'verdict' => $verdict,
                'comment' => filled($request->validated('comment')) ? trim($request->validated('comment')) : null,
            ],
        );

        return redirect()
            ->route('reports.show', $report)
            ->with('status', $verdict === 'confirm'
                ? 'Terima kasih, konfirmasi Anda tercatat.'
                : 'Sanggahan Anda tercatat.');
    }

    public function destroy(Request $request, DamageReport $report)
    {
        $report->verifications()->where('user_id', $request->user()->id)->delete();

        return redirect()->route('reports.show', $report)->with('status', 'Suara Anda ditarik.');
    }
}
