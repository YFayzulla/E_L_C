@extends('template.master')
@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">Groups</h5>
            <div class="dt-action-buttons text-end pt-3 pt-md-0">
                <div class="dt-buttons btn-group flex-wrap">
                    <div class="btn-group">
                        <a class="btn buttons-collection dropdown-toggle btn-label-primary me-2" tabindex="0"
                           aria-controls="DataTables_Table_0" type="button" id="dropdownMenuButton"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <span><i class="bx bx-export me-sm-1"></i> <span
                                    class="d-none d-sm-inline-block">Export</span></span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <li><a class="dropdown-item" href="{{URL::to('/group/pdf') }}"><i
                                        class="bx bxs-file-pdf me-1"></i> Pdf</a></li>
                        </ul>
                    </div>
                    <a href="{{ route('group.create')  }}" class="btn btn-secondary create-new btn-primary" tabindex="0"
                       aria-controls="DataTables_Table_0">

                        <span><i class="bx bx-plus me-sm-1"></i> <span
                                class="d-none d-sm-inline-block">Add New Group</span></span>
                    </a>
                </div>
            </div>
        </div>

        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                <tr>
                    <th>id</th>
                    <th>Name</th>
                    <th>start time</th>
                    <th>finish time</th>
                    <th>cost</th>
                    <th class="text-center">action</th>
                </tr>
                </thead>
                @foreach($groups as $group)
                    <tbody id="myTable" class="table-group-divider">
                    <tr>
                        <th>{{$loop->index+1}}</th>
                        <th>{{$group->name}}</th>
                        <th>{{$group->start_time}}</th>
                        <th>{{$group->finish_time}}</th>
                        <th>{{number_format($group->monthly_payment ,0, '.', ' ')}}</th>
                        <th class="d-flex">
                            <a href="{{route('group.students',$group->id)}}" class="btn btn-outline-info m-1">
                                <i
                                    class="bx bx-show-alt"></i>
                            </a>
                            <a href="{{route('group.attendance',$group->id)}}" class="btn btn-outline-info m-1">
                                <img style="width: 20px"
                                     src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAYAAAAeP4ixAAAACXBIWXMAAAsTAAALEwEAmpwYAAADzklEQVR4nO2ZbahOWRTHf9x7mclLxDwUhcnrXBEfiPg2+CAvH6amELrDFxnmUncU84WiyUt8xxSXECFS00zJWwhh5rpmfJlJZshrrgkX42rVf9fu6T7nOc9zzmMf5Ve77nPOXuuufc5ea6+1DnzkIx8cA4H5wGpgm8YaYArQiYzTEVgINAFtEeM2MIOM0hs47xn7CDgArAO+1dgI/K77r4E9wHQyRA/gmgz8G5gLVBWY20EL8t/QcaAbGWC7DLKn3SfGfFvkKKBBb85kjxCYAdomL4EhZcgP9hbzJQFZKSP2JtCxSjr2EZDDMsL8olyGSsc/BOS6jBiTMGybjrdAZwLhzgzb60l4JT29CMRvMsCiUBJeSE+wMHxFBkxIoOMz6XhMQPbLiK8T6JgoHZcIyA8y4scEOpZIxy4CMltG/JJAxyHpWERA5siIJxH5VRRV8g3TMY+AnJERq5QQlkODdJwmIM0pHIijpKOZgLj9XZdAxzfScZCA1Hl5Un0Z8ibzr3TMJyBWf5/1DrRS/KSD5+gngRoCY4neXRk0rAS54ZK5l6WGxE8yymr0uKyVjMlmBpdmPI6ZwVqd/1Ayk8gYJ2TYKXVVCmEZ7s8pZAQVo7/3lMdHzBuhOS3AIDLKRRk5LmLOsCwcgMW4KiNHF2mn2pybeddrgK+ARt37D3imvxt1r+Z9pyxRYbiv5mz1rs0CbhVptbZpjs2tKP2Ap/qH5i9Rzn5Mma81HjZ4hjYB3wG1QBeNWmUAN7wmxXrJps5wr20aJxK5LeIW0QosLVIK2L1lXqPCFpMalrnuVKfRtU3tzcRhlp5ua4ldxilajMnOJAFVMuJXb0u8UQ+4a0wdNZ5P2JsoleWez5QcALoD3wN/eQt4DuwoMcdCEcj5RDmVZbUXWExX7Cx1MXDfW8CfcsCelEej9Jhjx2WQwrtVpcYK6dgdR9iixlFvAdaymZqgrHX8IX1fxJw/GXggmQu6NlK/TVckn3o1ubX+F6QY8lra6Sye1YPKtVO8tXofhWyLI1mX6kSyxfvml7S3G2chlz2/ycl3Nnu7YVOeP8VayOfA/4pGY0kft7XssHPkvG+MTV423VqgJxBra63XJDsjKoFz9vw6P+ctpk0Bxvwj6iNTpLOf06RpVAYXfm+0E35z8pWLEWl+tZLJouHXfddLe9xRfe4fiJZ2lEp9nAPxkwotoi2v7eNSlFdKO+IyNa0UJU2cL5phy7VlClGtN1GRpDEpHWWQPV3nMysUkbpqjJRjN7+PND4ptkXiFlaZ2U6FyC91rcy1YW/CQmzBUvcdr7lrHKl/57wAAAAASUVORK5CYII=">
                            </a>
                            <a href="{{route('group.edit',$group->id)}}" class="btn-outline-warning btn m-1">
                                <i class='bx bx-edit-alt'></i>
                            </a>
                            <form action="{{route('group.destroy',$group->id)}}" method="post"
                                  onsubmit="return confirm('are you sure for deleting ');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="" class="btn-outline-danger btn m-1">
                                    <i class='bx bx-trash-alt'></i>
                                </button>
                            </form>
                        </th>
                    </tr>
                    </tbody>
                @endforeach
            </table>
        </div>

@endsection
