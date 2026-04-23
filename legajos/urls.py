from django.urls import path, include
from . import views

urlpatterns = [
    path('', views.home, name="legajos"),
    path('registrar/', views.registrar_legajos, name="registrar_legajos"),
    path('editar/<id>', views.editar, name="editar"),
    path('bajar/<id>', views.bajar, name="bajar"),
    path('alta/<id>', views.alta, name="alta"),

    path('editar/', views.editar_no, name="editar"),
    path('bajar/', views.bajar_no, name="bajar"),
    path('alta/', views.alta_no, name="alta"),
]
