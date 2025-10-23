@can('see_team')
    <div class="col-12">
        <div class="modern-team-card">
            <div class="team-card-header">
                <div class="team-header-content">
                    <div class="team-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="team-header-text">
                        <h4 class="team-title mb-0">Your Team Ongoings</h4>
                        <small>Track your team members' progress</small>
                    </div>
                </div>
            </div>
            <div class="team-card-body">
                @if (count($your_team) > 0)
                    @foreach ($your_team as $member)
                        <div class="team-member-section">
                            <div class="member-header">
                                <div class="member-avatar">
                                    {{ strtoupper(substr($member['name'], 0, 1)) }}
                                </div>
                                <div class="member-info">
                                    <h5 class="member-name">{{ $member['name'] }}</h5>
                                    <span class="member-count">{{ count($member['ongoings']) }}
                                        {{ count($member['ongoings']) === 1 ? 'payreq' : 'payreqs' }} ongoing</span>
                                </div>
                            </div>
                            @if (count($member['ongoings']) > 0)
                                <div class="member-payreqs">
                                    @foreach ($member['ongoings'] as $payreq)
                                        <div class="payreq-row">
                                            <div class="payreq-description">
                                                <i class="fas fa-file-alt text-muted mr-2"></i>
                                                {{ $payreq['description'] }}
                                            </div>
                                            <div class="payreq-meta">
                                                <span
                                                    class="payreq-status badge badge-{{ strtolower($payreq['status']) }}-team">
                                                    {{ $payreq['status'] }}
                                                </span>
                                                <span class="payreq-amount">
                                                    Rp {{ $payreq['amount'] }}
                                                </span>
                                                <span
                                                    class="payreq-days {{ $payreq['days'] > 7 ? 'text-danger' : 'text-muted' }}">
                                                    <i class="fas fa-clock mr-1"></i>{{ $payreq['days'] }} days
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="empty-state">
                        <i class="fas fa-user-friends"></i>
                        <p>No team data available</p>
                        <small>Team information will appear here</small>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        .modern-team-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .modern-team-card:hover {
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.12);
        }

        .team-card-header {
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
            padding: 20px;
        }

        .team-header-content {
            display: flex;
            align-items: center;
        }

        .team-icon {
            background: rgba(255, 255, 255, 0.2);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .team-icon i {
            font-size: 24px;
            color: #fff;
        }

        .team-header-text h4 {
            color: #fff;
            font-size: 18px;
            font-weight: 600;
        }

        .team-header-text small {
            color: rgba(255, 255, 255, 0.8);
            font-size: 12px;
        }

        .team-card-body {
            padding: 20px;
        }

        .team-member-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .team-member-section:last-child {
            margin-bottom: 0;
        }

        .team-member-section:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .member-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .member-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: bold;
            font-size: 18px;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .member-info {
            flex: 1;
        }

        .member-name {
            font-size: 16px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 3px;
        }

        .member-count {
            font-size: 13px;
            color: #6c757d;
        }

        .member-payreqs {
            padding-left: 60px;
        }

        .payreq-row {
            background: #fff;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            transition: all 0.3s ease;
        }

        .payreq-row:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transform: translateX(5px);
        }

        .payreq-row:last-child {
            margin-bottom: 0;
        }

        .payreq-description {
            flex: 1;
            font-size: 14px;
            color: #495057;
            margin-bottom: 5px;
        }

        .payreq-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .payreq-status {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            padding: 4px 10px;
        }

        .badge-draft-team {
            background: #e7f3ff;
            color: #0056b3;
        }

        .badge-submitted-team {
            background: #fff3cd;
            color: #856404;
        }

        .badge-approved-team {
            background: #d4edda;
            color: #155724;
        }

        .payreq-amount {
            font-size: 13px;
            font-weight: 600;
            color: #495057;
        }

        .payreq-days {
            font-size: 12px;
        }
    </style>
@endcan
