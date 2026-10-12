<!doctype html>
<html><head><meta charset="utf-8"><style>
@page{size:A4;margin:15mm}
body{font-family:DejaVu Sans, sans-serif;color:#252525;font-size:10px;line-height:1.4}
.top{display:table;width:100%;border-bottom:3px solid #c89835;padding-bottom:12px}
.cell{display:table-cell;vertical-align:top;width:70%}
.photo-cell{width:30%;text-align:right}
.logo{max-width:90px;max-height:45px}
.photo{width:100px;height:120px;object-fit:cover;border:1px solid #ddd}
.placeholder{display:inline-block;width:100px;height:120px;border:1px solid #c89835;text-align:center;padding-top:45px;color:#a97718;font-weight:bold}
.brand{font-size:20px;color:#a97718;font-weight:bold}
.title{font-size:18px;color:#a97718;font-weight:bold;margin-top:8px}
.section{margin-top:13px;border:1px solid #e0d8c7;padding:10px;background:#fff}
.section-title{font-size:11px;font-weight:bold;color:#a97718;border-bottom:1px solid #e5d4ad;padding-bottom:4px;margin-bottom:7px;text-transform:uppercase;letter-spacing:0.5px}
.layout-table{width:100%;border-collapse:collapse;table-layout:fixed}
.layout-table td{padding:4px 8px 4px 0;vertical-align:top}
.label{color:#777;font-size:8.5px;text-transform:uppercase;display:block;margin-bottom:2px}
.value{font-weight:bold;font-size:10px;color:#222}
.pill{display:inline-block;border:1px solid #c89835;padding:3px 8px;margin:2px;border-radius:6px;background:#fdfbf7;font-weight:bold}
.muted{color:#777}
.badge-status{display:inline-block;padding:2px 8px;border-radius:4px;font-weight:bold;font-size:9px}
.badge-verified{background:#dcfce7;color:#15803d;border:1px solid #86efac}
.badge-pending{background:#fef3c7;color:#b45309;border:1px solid #fcd34d}
.badge-rejected{background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5}
.badge-partially_verified{background:#e0f2fe;color:#0369a1;border:1px solid #7dd3fc}
.footer{margin-top:16px;border-top:1px solid #ddd;padding-top:8px;color:#777;font-size:8.5px;text-align:center}
</style></head><body>
@php($w=$snapshot['worker']??[])
<div class="top">
  <div class="cell">
    @if($logoData)<img class="logo" src="{{ $logoData }}"><br>@endif
    <div class="brand">{{ $snapshot['agency']['business_name']??'Helper Home' }}</div>
    <div>{{ $snapshot['agency']['tagline']??'Home Care & Domestic Services' }}</div>
    <div class="title">WORKER PROFILE / RESUME</div>
  </div>
  <div class="photo-cell">
    @if($photoData)<img class="photo" src="{{ $photoData }}">@else<span class="placeholder">HELPER<br>HOME</span>@endif
  </div>
</div>

<div class="section">
  <div class="section-title">Worker Profile</div>
  <table class="layout-table">
    <tr>
      <td style="width:33%"><span class="label">Worker Code</span><span class="value">{{ $w['worker_code']??'—' }}</span></td>
      <td style="width:33%"><span class="label">Name</span><span class="value">{{ $w['name']??'—' }}</span></td>
      <td style="width:34%"><span class="label">City / State</span><span class="value">{{ collect([$w['city']??null,$w['state']??null])->filter()->implode(', ') ?: '—' }}</span></td>
    </tr>
  </table>
</div>

<div class="section">
  <div class="section-title">Personal Details</div>
  <table class="layout-table">
    <tr>
      <td style="width:25%"><span class="label">Age / Date of Birth</span><span class="value">{{ $w['age']??'—' }} @if(!empty($w['date_of_birth'])) ({{ $w['date_of_birth'] }}) @endif</span></td>
      <td style="width:25%"><span class="label">Gender</span><span class="value">{{ !empty($w['gender'])?str($w['gender'])->headline():'—' }}</span></td>
      <td style="width:25%"><span class="label">Marital Status</span><span class="value">{{ !empty($w['marital_status'])?str($w['marital_status'])->headline():'—' }}</span></td>
      <td style="width:25%"><span class="label">Expected Salary</span><span class="value">{{ isset($w['salary_expectation'])&&$w['salary_expectation']!==null?'₹'.number_format((float)$w['salary_expectation'],2):'—' }}</span></td>
    </tr>
    <tr>
      <td colspan="4" style="padding-top:6px"><span class="label">Address</span><span class="value">{{ collect([$w['address_line_1']??null,$w['address_line_2']??null,$w['city']??null,$w['state']??null,$w['pincode']??null])->filter()->implode(', ') ?: '—' }}</span></td>
    </tr>
  </table>
</div>

<div class="section">
  <div class="section-title">Professional Details</div>
  <div style="margin-bottom:8px">
    <span class="label">Service Categories</span>
    @forelse($snapshot['services']??[] as $service)
      <span class="pill">{{ $service['name'] }}@if($service['experience_years'] !== null) · {{ $service['experience_years'] }} yrs @endif</span>
    @empty
      <span class="muted">—</span>
    @endforelse
  </div>
  <table class="layout-table">
    <tr>
      <td style="width:25%"><span class="label">Overall Experience</span><span class="value">{{ $w['years_of_experience']??'—' }} @if(isset($w['years_of_experience'])) years @endif</span></td>
      <td style="width:25%"><span class="label">Preferred Duty Type</span><span class="value">{{ $w['preferred_duty_type']??'—' }}</span></td>
      <td style="width:25%"><span class="label">Preferred Location</span><span class="value">{{ $w['preferred_work_location']??'—' }}</span></td>
      <td style="width:25%"><span class="label">Availability</span><span class="value">{{ !empty($w['availability_status'])?str($w['availability_status'])->headline():'—' }}</span></td>
    </tr>
  </table>
  @if(!empty($w['experience_notes']))
    <div style="margin-top:6px;padding-top:6px;border-top:1px dashed #eee"><span class="label">Experience / Skills Notes</span><span class="value" style="font-weight:normal;color:#444">{{ $w['experience_notes'] }}</span></div>
  @endif
</div>

<div class="section">
  <div class="section-title">Verification Status</div>
  @php($docStatus = strtolower($snapshot['verification']['document']??'pending'))
  @php($polStatus = strtolower($snapshot['verification']['police']??'pending'))
  @php($bgStatus = strtolower($snapshot['verification']['background']??'pending'))
  @php($overallStatus = strtolower($snapshot['verification']['overall']??'pending'))
  <table class="layout-table">
    <tr>
      <td style="width:25%"><span class="label">Document Verification</span><span class="badge-status badge-{{ $docStatus }}">{{ str($docStatus)->headline() }}</span></td>
      <td style="width:25%"><span class="label">Police Verification</span><span class="badge-status badge-{{ $polStatus }}">{{ str($polStatus)->headline() }}</span></td>
      <td style="width:25%"><span class="label">Background Verification</span><span class="badge-status badge-{{ $bgStatus }}">{{ str($bgStatus)->headline() }}</span></td>
      <td style="width:25%"><span class="label">Overall Verification</span><span class="badge-status badge-{{ $overallStatus }}">{{ str($overallStatus)->headline() }}</span></td>
    </tr>
  </table>
</div>

@if(!empty($snapshot['interview']))
<div class="section">
  <div class="section-title">Interview Assessment</div>
  <table class="layout-table">
    <tr>
      <td style="width:25%"><span class="label">Result</span><span class="value">{{ str($snapshot['interview']['result']??'pending')->headline() }}</span></td>
      <td style="width:37%"><span class="label">Skills</span><span class="value">{{ $snapshot['interview']['skills_assessment']??'—' }}</span></td>
      <td style="width:38%"><span class="label">Communication</span><span class="value">{{ $snapshot['interview']['communication_assessment']??'—' }}</span></td>
    </tr>
  </table>
</div>
@endif

@if(!empty($snapshot['references']))
<div class="section">
  <div class="section-title">References</div>
  <table class="layout-table">
    @foreach(array_chunk($snapshot['references'], 2) as $row)
      <tr>
        @foreach($row as $reference)
          <td style="width:50%"><span class="value">{{ $reference['name']??'—' }}</span> <span class="muted">({{ $reference['relation']??'—' }})</span></td>
        @endforeach
        @if(count($row) === 1)<td style="width:50%"></td>@endif
      </tr>
    @endforeach
  </table>
</div>
@endif

<div class="footer">
  {{ $snapshot['agency']['business_name']??'Helper Home' }} · {{ $snapshot['agency']['phone']??'' }} · {{ $snapshot['agency']['email']??'' }}<br>
  {{ $snapshot['agency']['address']??'' }}
</div>
</body></html>
