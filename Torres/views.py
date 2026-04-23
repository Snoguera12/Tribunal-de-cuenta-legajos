from django.shortcuts import render, redirect
from django.views.generic import View
from django.contrib import messages
from django.contrib.auth import login, logout, authenticate
from django.contrib.auth.forms import UserCreationForm, AuthenticationForm

RAIZ_APP = "/"

def home(request):
    return render(request,"index.html")

class registrar_usuario(View):
    def get(self, request):
        if not request.user.is_authenticated:
            form = UserCreationForm()
            return render(request, "registrar.html", {'form':form})
        else:
            return redirect('home')

    def post(self, request):
        if not request.user.is_authenticated:
            form = UserCreationForm(request.POST)

            if form.is_valid():
                usuario = form.save()
                login(request, usuario)
                return redirect('home')
            else:
                for msg in form.error_messages:
                    messages.error(request, form.error_messages[msg])
                
                return render(request, "registrar_legajos.html", {'form':form})
        else:
            return redirect('home')


def torres_logout(request):
    if request.user.is_authenticated:
        logout(request)
    return redirect('home')

def torres_login(request):
    if not request.user.is_authenticated:
        if request.method == "POST":
            form = AuthenticationForm(request, data=request.POST)
            if form.is_valid():
                nombre = form.cleaned_data.get("username")
                contra = form.cleaned_data.get("password")
                usuario = authenticate(username=nombre, password=contra)
                if usuario is not None:
                    login(request, usuario)
                    return redirect('home')
                else:
                    messages.error(request, "usuario no válido")
            else:
                messages.error(request, "Usuario y/o Contraseña incorrecta.")

        form = AuthenticationForm()
        return render(request, 'login.html', {"form":form})
    else:
        return redirect('home')
