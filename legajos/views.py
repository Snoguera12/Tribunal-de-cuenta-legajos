from django.shortcuts import render, redirect
from django.template import Template, Context
from django.core.paginator import Paginator
from django.utils import timezone
from .forms import Legajoform
from .models import Legajo

# Create your views here.

RAIZ_APP = '/legajos/'

def home(request):
    if request.user.is_authenticated:
        if request.user.is_staff:
            legajos = Legajo.objects.all()
        else:
            legajos = Legajo.objects.filter(estado=1)

        n_legajo = request.GET.get('n_legajo')
        nombre = request.GET.get('nombre')
        dni = request.GET.get('dni')
        cargo = request.GET.get('cargo')
        
        if n_legajo:
            legajos = legajos.filter(n_legajo=n_legajo)
        if nombre:
            legajos = legajos.filter(nombre__icontains=nombre)
        if dni:
            legajos = legajos.filter(dni=dni)
        if cargo:
            legajos = legajos.filter(cargo__icontains=cargo)
        
        paginator = Paginator(legajos, 5)
        page_numero = request.GET.get('page')
        page_obj = paginator.get_page(page_numero)

        return render(request, "home_legajos.html", {'Legajos':page_obj})
    else:
        return redirect('login')

def registrar_legajos(request):
    if request.user.is_authenticated:
        if request.method == "POST":
            n_legajo = request.POST['n_legajo']
            nombre   = request.POST['nombre']
            dni      = request.POST['dni']
            cargo    = request.POST['cargo']
            tabla_legajos = Legajo.objects.create(
                n_legajo=n_legajo,
                nombre=nombre,
                dni=dni,
                cargo=cargo,
                create_date=timezone.now()
            )
            
            tabla_legajos.save()
            return redirect("registrar_legajos")
        else:
            form = Legajoform()
        
        return render(request, "registrar_legajos.html", {'form_legajo':form})
    else:
        return redirect('login')

def editar(request, id):
    if request.user.is_authenticated:
        legajos = Legajo.objects.get(id=id)
        print(legajos.estado)
        if legajos.estado == 1 or request.user.is_staff:
            if request.method == "POST":
                legajos.n_legajo = request.POST['n_legajo']
                legajos.nombre = request.POST['nombre']
                legajos.dni = request.POST['dni']
                legajos.cargo = request.POST['cargo']
                legajos.update_date = timezone.now()
                legajos.save()

                return redirect("legajos")
            else:
                n_legajo = legajos.n_legajo
                nombre   = legajos.nombre
                dni      = legajos.dni
                cargo    = legajos.cargo

                data = {'n_legajo': n_legajo, 'nombre':nombre, 'dni': dni, 'cargo':cargo,}
                form = Legajoform(initial=data)
            
                return render(request, "editar_legajos.html", {'form_legajo':form, 'ID':id})
        else:
            return redirect('legajos')
    
    return redirect("home")

def bajar(request, id):
    if request.user.is_authenticated:
        legajos = Legajo.objects.get(id=id)
        legajos.estado = 0
        legajos.save()
    
    return redirect("legajos")

def alta(request, id):
    if request.user.is_authenticated:
        legajos = Legajo.objects.get(id=id)
        legajos.estado = 1
        legajos.save()
    
    return redirect("legajos")

def editar_no(request):
    if request.user.is_authenticated:
        return redirect('legajos')
    else:
        return redirect('home')

def bajar_no(request):
    if request.user.is_authenticated:
        return redirect('legajos')
    else:
        return redirect('home')

def alta_no(request):
    if request.user.is_authenticated:
        return redirect('legajos')
    else:
        return redirect('home')