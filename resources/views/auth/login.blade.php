    <div class="login-box">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h1 class="mb-0 text-center">SMPN 19 Tikep</h1>
            </div>
            <div class="card-body login-card-body">
                <p class="login-box-msg">Sign in to start your session</p>

                <form wire:submit.prevent="login">
                    @csrf
                    <div class="input-group mb-1">
                        <div class="form-floating">
                            <input id="loginEmail" type="email" class="form-control" wire:model="email"
                                placeholder="masukkan email" value="{{ old('email') }}" />
                            <label for="loginEmail">Email</label>
                        </div>
                        <div class="input-group-text">
                            <span class="bi bi-envelope"></span>
                        </div>
                    </div>
                    @error('email')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                    <div class="input-group mb-1">
                        <div class="form-floating">
                            <input id="loginPassword" type="password" class="form-control" wire:model="password"
                                placeholder="masukkan password" />
                            <label for="loginPassword">Password</label>
                        </div>
                        <div class="input-group-text">
                            <span class="bi bi-lock-fill"></span>
                        </div>
                    </div>
                    @error('password')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                    <!--begin::Row-->
                    <div class="row mt-3">
                        <div class="col-8 d-inline-flex align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model="remember"
                                    id="flexCheckDefault" />
                                <label class="form-check-label" for="flexCheckDefault"> Ingat saya </label>
                            </div>
                        </div>
                        <!-- /.col -->
                        <div class="col-4">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success"><span wire:loading.remove
                                        wire:target="login">Log In</span>
                                    <span wire:loading wire:target="login">
                                        <span class="spinner-border spinner-border-sm" role="status"
                                            aria-hidden="true"></span> Memeriksa...
                                    </span></button>

                            </div>
                        </div>
                        <!-- /.col -->
                    </div>
                    <!--end::Row-->
                </form>
            </div>
            <!-- /.login-card-body -->
        </div>
    </div>
