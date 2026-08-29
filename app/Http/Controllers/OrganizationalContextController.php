<?php

namespace App\Http\Controllers;

use App\Http\Requests\SwitchOrganizationalContextRequest;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Http\RedirectResponse;

class OrganizationalContextController extends Controller
{
    public function update(
        SwitchOrganizationalContextRequest $request,
        OrganizationalContext $context
    ): RedirectResponse {
        $context->setActiveContext(
            $request->user(),
            (int) $request->validated('organizational_unit_id')
        );

        return back()->with('success', 'Organizational context changed.');
    }
}
