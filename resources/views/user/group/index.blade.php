@extends('template.master')
@section('content')
    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">


        <h1 class="text-center">Groups</h1>

        <ul class="nav nav-pills flex-column flex-md-row mb-3">
            <li class="nav-item me-2 mt-2">
                <a class="btn btn-outline-success" href="{{route('group.create')}}">
                    <i class="bx bx-plus"></i>
                </a>
            </li>
            <li class="nav-item me-2 mt-2">
                <a class="btn btn-danger" href="{{ URL::to('/group/pdf') }}">
                    Report
                </a>
            </li>
        </ul>

        <div class="table-responsive text-nowrap">

            <table class="table">
                <thead>
                <tr>
                    <th>id</th>
                    <th>Name</th>
                    <th>opened date</th>
                    <th>start time</th>
                    <th>finish time</th>
                    <th>level</th>
                    <th>cost</th>
                    <th class="text-center">action</th>
                </tr>
                </thead>
                @foreach($groups as $group)
                    <tbody id="myTable" class="table-group-divider">
                    <tr>
                        <th>{{$loop->index+1}}</th>
                        <th>{{$group->name}}</th>
                        <th>{{$group->created_at}}</th>
                        <th>{{$group->start_time}}</th>
                        <th>{{$group->finish_time}}</th>
                        <th>{{$group->level}}</th>
                        <th>{{$group->monthly_payment}}</th>
                        <th class="d-flex">
                            <a href="{{route('group.students',$group->id)}}" class="btn btn-outline-info m-1">
                                <i
                                    class="bx bx-show-alt"></i>
                            </a>
                            <a href="{{route('group.attendance',$group->id)}}" class="btn btn-outline-info m-1">
                                <img style="width: 20px"src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAYAAAAeP4ixAAAACXBIWXMAAAsTAAALEwEAmpwYAAADzklEQVR4nO2ZbahOWRTHf9x7mclLxDwUhcnrXBEfiPg2+CAvH6amELrDFxnmUncU84WiyUt8xxSXECFS00zJWwhh5rpmfJlJZshrrgkX42rVf9fu6T7nOc9zzmMf5Ve77nPOXuuufc5ea6+1DnzkIx8cA4H5wGpgm8YaYArQiYzTEVgINAFtEeM2MIOM0hs47xn7CDgArAO+1dgI/K77r4E9wHQyRA/gmgz8G5gLVBWY20EL8t/QcaAbGWC7DLKn3SfGfFvkKKBBb85kjxCYAdomL4EhZcgP9hbzJQFZKSP2JtCxSjr2EZDDMsL8olyGSsc/BOS6jBiTMGybjrdAZwLhzgzb60l4JT29CMRvMsCiUBJeSE+wMHxFBkxIoOMz6XhMQPbLiK8T6JgoHZcIyA8y4scEOpZIxy4CMltG/JJAxyHpWERA5siIJxH5VRRV8g3TMY+AnJERq5QQlkODdJwmIM0pHIijpKOZgLj9XZdAxzfScZCA1Hl5Un0Z8ibzr3TMJyBWf5/1DrRS/KSD5+gngRoCY4neXRk0rAS54ZK5l6WGxE8yymr0uKyVjMlmBpdmPI6ZwVqd/1Ayk8gYJ2TYKXVVCmEZ7s8pZAQVo7/3lMdHzBuhOS3AIDLKRRk5LmLOsCwcgMW4KiNHF2mn2pybeddrgK+ARt37D3imvxt1r+Z9pyxRYbiv5mz1rs0CbhVptbZpjs2tKP2Ap/qH5i9Rzn5Mma81HjZ4hjYB3wG1QBeNWmUAN7wmxXrJps5wr20aJxK5LeIW0QosLVIK2L1lXqPCFpMalrnuVKfRtU3tzcRhlp5ua4ldxilajMnOJAFVMuJXb0u8UQ+4a0wdNZ5P2JsoleWez5QcALoD3wN/eQt4DuwoMcdCEcj5RDmVZbUXWExX7Cx1MXDfW8CfcsCelEej9Jhjx2WQwrtVpcYK6dgdR9iixlFvAdaymZqgrHX8IX1fxJw/GXggmQu6NlK/TVckn3o1ubX+F6QY8lra6Sye1YPKtVO8tXofhWyLI1mX6kSyxfvml7S3G2chlz2/ycl3Nnu7YVOeP8VayOfA/4pGY0kft7XssHPkvG+MTV423VqgJxBra63XJDsjKoFz9vw6P+ctpk0Bxvwj6iNTpLOf06RpVAYXfm+0E35z8pWLEWl+tZLJouHXfddLe9xRfe4fiJZ2lEp9nAPxkwotoi2v7eNSlFdKO+IyNa0UJU2cL5phy7VlClGtN1GRpDEpHWWQPV3nMysUkbpqjJRjN7+PND4ptkXiFlaZ2U6FyC91rcy1YW/CQmzBUvcdr7lrHKl/57wAAAAASUVORK5CYII=">
                            </a>
                            <a href="{{route('group.show',$group->id)}}" class="btn btn-outline-primary m-1">
                                <img style="width: 20px" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAYAAAAeP4ixAAAACXBIWXMAAAsTAAALEwEAmpwYAAABAklEQVR4nO2aUQ6CMBBE54tDGf0XDyZ4TQGvMYakTcxGDV1bimResn8s3dktE0IBxD65ABgAMDHmnBYbYnCIiHEvXVwD4AbgkVBUKkvvOwHoQ03J9I7ulhLCEJ1HyBSSDwkFpbI07/gymWKLpF679hqwibW2VkRCmNCREvbL0hN5RxsK8og4b0lICSghBk0kE9TWcnTk1/ewb9dwzYnsRkhJKCEGTSQT1MNukGtlgnItg1wrE5RrGeRamaBcyyDXygTlWga5ViYo1zLItTJBba2FHVk7in3X+nshtaG3nng8PR8N1+YUahk9yV3F7cQPcfUIaYKYOJmaMQYRrl84BCrwBAlgsh+R7ve3AAAAAElFTkSuQmCC">
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
