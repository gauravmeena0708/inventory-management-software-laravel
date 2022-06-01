<nav id="sidebar">
            <div class="sidebar-header">
                <h3><a class="navbar-brand" href="{{ url('/') }}">
                <i class="fas fa-home"></i> {{ config('app.name', 'Admin@NDC') }}
                </a></h3>
            </div>

            <ul class="list-unstyled components sidebar">
                <li class="{{Request::is('/')?'active':''}}"><a href="{{route('home')}}">Home</a></li>
                <li class="{{Request::is('files')?'active':''}}"><a href="{{route('files.index')}}">Files</a></li>
                <li class="{{Request::is('agreements')?'active':''}}"><a href="{{route('agreements.index')}}">Agreements</a></li>
                <li>
                    <a href="#paymentSubmenu" data-toggle="collapse" aria-expanded="{{Request::is('payments*')?'true':'false'}}" class="dropdown-toggle">Payments</a>
                    <ul class="collapse list-unstyled {{Request::is('payments*')?'show':''}}" id="paymentSubmenu">
                        <li class="{{Request::is('payments')?'active':''}}"><a href="{{route('payments.index')}}">All</a></li>
                        <li class="{{Request::is('payments/due')?'active':''}}"><a href="{{route('payments.due')}}">Due</a></li>
                        <li class="{{Request::is('payments/completed')?'active':''}}"><a href="{{route('payments.completed')}}">Completed</a> </li>
                        <li class="{{Request::is('payments/generate')?'active':''}}"><a href="{{route('payments.generate')}}">Generate</a> </li>
                    </ul>
                </li>
                <li class="{{Request::is('consumables')?'active':''}}"><a href="{{route('consumables.index')}}">Consumables</a></li>
                
                <li>
                    <a href="#stockSubmenu" data-toggle="collapse" aria-expanded="{{Request::is('stock/*')?'true':'false'}}" class="dropdown-toggle">Stock Register</a>
                    <ul class="collapse list-unstyled {{Request::is('stock*')?'show':''}}" id="stockSubmenu">
                        <li class="{{Request::is('stock')?'active':''}}"><a href="{{route('entries.index')}}">All</a></li>
                        <li class="{{Request::is('stock/purchase')?'active':''}}"><a href="{{route('entries.purchase')}}">Purchases</a></li>
                        <li class="{{Request::is('stock/issue')?'active':''}}"><a href="{{route('entries.issue')}}">Issue</a> </li>
                    </ul>
                </li>
                <li class="{{Request::is('officials')?'active':''}}"><a href="{{route('officials.index')}}">Officials</a></li>
                <li class="{{Request::is('locations')?'active':''}}"><a href="{{route('locations.index')}}">Locations</a></li>
                <li class="{{Request::is('manufacturers')?'active':''}}"><a href="{{route('manufacturers.index')}}">Manufacturers</a></li>    
                <li>
                    <a href="#developerSubmenu" data-toggle="collapse" aria-expanded="{{Request::is('developers/*')?'true':'false'}}" class="dropdown-toggle">Developers</a>
                    <ul class="collapse list-unstyled {{Request::is('developers*')?'show':''}}" id="developerSubmenu">
                        <li class="{{Request::is('developers')?'active':''}}"><a href="{{route('developers.index')}}">All</a></li>
                        <li class="{{Request::is('developers/active')?'active':''}}"><a href="{{route('developers.active')}}">Active</a></li>
                        <li class="{{Request::is('developers/discontinued')?'active':''}}"><a href="{{route('developers.discontinued')}}">Discontinued</a> </li>
                    </ul>
                </li>
                <li>
                    <a href="#assetSubmenu" data-toggle="collapse" aria-expanded="{{Request::is('assets/*')?'true':'false'}}" class="dropdown-toggle">Assets</a>
                    <ul class="collapse list-unstyled {{Request::is('assets*')?'show':''}}" id="assetSubmenu">
                        <li class="{{Request::is('assets/servers')?'active':''}}"><a href="{{route('servers.index')}}">Servers</a></li>
                        <li class="{{Request::is('assets/switches')?'active':''}}"><a href="{{route('switches.index')}}">Switches</a></li>
                        <li class="{{Request::is('assets/desktops')?'active':''}}"><a href="{{route('desktops.index')}}">Desktops</a></li>
                        <li class="{{Request::is('assets/laptops')?'active':''}}"><a href="{{route('laptops.index')}}">Laptops</a></li>
                        <li class="{{Request::is('assets/storges')?'active':''}}"><a href="{{route('storages.index')}}">Storages</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#taskSubmenu" data-toggle="collapse" aria-expanded="{{Request::is('tasks/*')?'true':'false'}}" class="dropdown-toggle">Tasks</a>
                    <ul class="collapse list-unstyled {{Request::is('tasks*')?'show':''}}" id="taskSubmenu">
                        <li class="{{Request::is('tasks/pending')?'active':''}}"><a href="{{route('tasks.pending')}}">Pending</a></li>
                        <li class="{{Request::is('tasks/comepleted')?'active':''}}"><a href="{{route('tasks.completed')}}">Completed</a></li>      
                    </ul>
                </li>
            </ul>
        </nav>